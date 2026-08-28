<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class ProcessWhatsAppQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-queue {--once : Process all pending messages and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes the WhatsApp message queue safely with delays';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsapp)
    {
        $lockPath = storage_path('framework/whatsapp_queue.lock');
        $lockFile = @fopen($lockPath, 'c+');
        
        if (!$lockFile) {
            $this->error('Failed to open lock file.');
            return;
        }

        if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
            fclose($lockFile);
            $this->info('WhatsApp queue processor is already running.');
            return;
        }

        $runOnce = $this->option('once');
        $this->info('WhatsApp queue processor daemon started' . ($runOnce ? ' (once mode)' : '') . '.');

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        while (true) {
            // Build the list of allowed contexts: session_id:shift_type
            $activeSessions = \App\Models\AcademicSession::all();
            $allowedContexts = [];

            foreach ($activeSessions as $session) {
                $shifts = ($session->shift_type === 'Regular') ? ['regular'] : ['morning', 'evening'];
                foreach ($shifts as $shift) {
                    $enabledSetting = Setting::where('key', "whatsapp_auto_send_enabled_{$shift}")
                        ->where('academic_session_id', $session->id)
                        ->first();
                    $enabledVal = $enabledSetting ? $enabledSetting->value : null;
                    if ($enabledVal === null) {
                        $enabledVal = Setting::where('key', "whatsapp_auto_send_enabled_{$shift}")
                            ->whereNull('academic_session_id')
                            ->value('value');
                    }
                    $enabled = ($enabledVal === 'true');

                    $forceSetting = Setting::where('key', "whatsapp_force_send_now_{$shift}")
                        ->where('academic_session_id', $session->id)
                        ->first();
                    $forceVal = $forceSetting ? $forceSetting->value : null;
                    if ($forceVal === null) {
                        $forceVal = Setting::where('key', "whatsapp_force_send_now_{$shift}")
                            ->whereNull('academic_session_id')
                            ->value('value');
                    }
                    $force = ($forceVal === 'true');

                    if ($runOnce || $force) {
                        $allowedContexts[] = "{$session->id}:{$shift}";
                        continue;
                    }

                    if ($enabled) {
                        $startSetting = Setting::where('key', "whatsapp_auto_send_start_{$shift}")
                            ->where('academic_session_id', $session->id)
                            ->first();
                        $startTime = $startSetting ? $startSetting->value : null;
                        if ($startTime === null) {
                            $startTime = Setting::where('key', "whatsapp_auto_send_start_{$shift}")
                                ->whereNull('academic_session_id')
                                ->value('value') ?: '09:00';
                        }

                        $endSetting = Setting::where('key', "whatsapp_auto_send_end_{$shift}")
                            ->where('academic_session_id', $session->id)
                            ->first();
                        $endTime = $endSetting ? $endSetting->value : null;
                        if ($endTime === null) {
                            $endTime = Setting::where('key', "whatsapp_auto_send_end_{$shift}")
                                ->whereNull('academic_session_id')
                                ->value('value') ?: '22:00';
                        }

                        $now = Carbon::now();
                        $start = Carbon::createFromTimeString($startTime);
                        $end = Carbon::createFromTimeString($endTime);

                        if ($now->between($start, $end)) {
                            $allowedContexts[] = "{$session->id}:{$shift}";
                        }
                    }
                }
            }

            // Fetch pending messages that fall within our allowed contexts
            $query = DB::table('whatsapp_queue')
                ->leftJoin('students', 'whatsapp_queue.student_id', '=', 'students.id')
                ->leftJoin('enrollments', function($join) {
                    $join->on('students.id', '=', 'enrollments.student_id')
                         ->where('enrollments.status', '=', 'active');
                })
                ->where('whatsapp_queue.status', 'pending');

            $query->where(function($q) use ($allowedContexts, $activeSessionId, $runOnce) {
                if ($runOnce) {
                    return;
                }

                if (in_array("{$activeSessionId}:morning", $allowedContexts)) {
                    $q->whereNull('whatsapp_queue.student_id');
                }

                foreach ($allowedContexts as $context) {
                    list($sessId, $shType) = explode(':', $context);
                    $q->orWhere(function($sub) use ($sessId, $shType) {
                        $sub->whereNotNull('whatsapp_queue.student_id')
                            ->where('enrollments.academic_session_id', $sessId)
                            ->where('enrollments.shift_type', $shType);
                    });
                }

                if (empty($allowedContexts)) {
                    $q->whereRaw('1 = 0');
                }
            });

            $messages = $query->select('whatsapp_queue.*')
                ->orderBy('whatsapp_queue.priority', 'desc')
                ->orderBy('whatsapp_queue.id', 'asc')
                ->limit(5)
                ->get();

            if ($messages->isEmpty()) {
                if ($runOnce) {
                    $this->info('No pending messages. Exiting once-mode.');
                    break;
                }
                sleep(10);
                continue;
            }

            if (!$whatsapp->isConnected()) {
                $this->error('WhatsApp service is not connected. Sleeping...');
                sleep(15);
                continue;
            }

            foreach ($messages as $msg) {
                $currentStatus = DB::table('whatsapp_queue')->where('id', $msg->id)->value('status');
                if ($currentStatus !== 'pending') {
                    continue;
                }

                $result = $whatsapp->sendMessage($msg->phone, $msg->message);

                if ($result['success'] ?? false) {
                    DB::table('whatsapp_queue')->where('id', $msg->id)->update([
                        'status' => 'sent',
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('whatsapp_queue')->where('id', $msg->id)->update([
                        'status' => 'failed',
                        'error_message' => $result['error'] ?? 'Unknown error',
                        'updated_at' => now(),
                    ]);
                }

                // Determine delay specifically for this message context
                $studentId = $msg->student_id;
                $msgSessionId = $activeSessionId;
                $msgShift = 'morning';
                if ($studentId) {
                    $enrollment = DB::table('enrollments')
                        ->where('student_id', $studentId)
                        ->where('status', 'active')
                        ->first();
                    if ($enrollment) {
                        $msgSessionId = $enrollment->academic_session_id;
                        $msgShift = $enrollment->shift_type;
                    }
                }

                $delaySetting = Setting::where('key', "whatsapp_queue_delay_{$msgShift}")
                    ->where('academic_session_id', $msgSessionId)
                    ->first();
                $delay = $delaySetting ? (int)$delaySetting->value : null;
                if ($delay === null) {
                    $delay = (int) (Setting::where('key', "whatsapp_queue_delay_{$msgShift}")
                        ->whereNull('academic_session_id')
                        ->value('value') ?: 5);
                }
                $delay = max(1, $delay);

                sleep($delay);
            }

            if ($runOnce) {
                break;
            }
        }

        flock($lockFile, LOCK_UN);
        fclose($lockFile);
        $this->info('WhatsApp queue processor daemon stopped.');
    }
}
