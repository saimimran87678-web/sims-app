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

        while (true) {
            $enabled = Setting::getGlobal('whatsapp_auto_send_enabled', 'false') === 'true';
            $forceSendNow = Setting::getGlobal('whatsapp_force_send_now', 'false') === 'true';

            // Check if we should keep running
            if (!$enabled && !$forceSendNow && !$runOnce) {
                $this->info('Processor stopping: both auto-send and force-send are disabled.');
                break;
            }

            // If not forcing and not running once, check the time window
            if (!$forceSendNow && !$runOnce) {
                $startTime = Setting::getGlobal('whatsapp_auto_send_start', '09:00');
                $endTime = Setting::getGlobal('whatsapp_auto_send_end', '22:00');
                
                $now = Carbon::now();
                $start = Carbon::createFromTimeString($startTime);
                $end = Carbon::createFromTimeString($endTime);

                if (!$now->between($start, $end)) {
                    $this->info('Processor stopping: outside configured time window (' . $startTime . ' - ' . $endTime . ').');
                    break;
                }
            }

            $delay = (int) Setting::getGlobal('whatsapp_queue_delay', 5);
            $delay = max(1, $delay);

            $messages = DB::table('whatsapp_queue')
                ->where('status', 'pending')
                ->orderBy('priority', 'desc')
                ->orderBy('id', 'asc')
                ->limit(5)
                ->get();

            if ($messages->isEmpty()) {
                if ($runOnce) {
                    $this->info('No pending messages. Exiting once-mode.');
                    break;
                }
                // SAVING MODE: Sleep for a longer period to save compute resources & power
                sleep(10);
                continue;
            }

            if (!$whatsapp->isConnected()) {
                $this->error('WhatsApp service is not connected. Sleeping...');
                sleep(15);
                continue;
            }

            foreach ($messages as $msg) {
                // Re-verify message is still pending before sending (in case it was paused/deleted in UI)
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
                
                // Sleep to pace messages and avoid WhatsApp ban
                sleep($delay);
            }

            if ($runOnce) {
                // Check if more pending messages exist
                $hasMore = DB::table('whatsapp_queue')->where('status', 'pending')->exists();
                if (!$hasMore) {
                    break;
                }
            }
        }

        flock($lockFile, LOCK_UN);
        fclose($lockFile);
        $this->info('WhatsApp queue processor daemon stopped.');
    }
}
