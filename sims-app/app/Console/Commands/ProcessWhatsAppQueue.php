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
    protected $signature = 'whatsapp:process-queue';

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
        $enabled = Setting::get('whatsapp_auto_send_enabled', 'false') === 'true';
        $forceSendNow = Setting::get('whatsapp_force_send_now', 'false') === 'true';

        if (!$enabled && !$forceSendNow) {
            return;
        }

        // Enforce time window only if we are NOT forcing sending now
        if (!$forceSendNow) {
            $startTime = Setting::get('whatsapp_auto_send_start', '09:00');
            $endTime = Setting::get('whatsapp_auto_send_end', '22:00');
            
            $now = Carbon::now();
            $start = Carbon::createFromTimeString($startTime);
            $end = Carbon::createFromTimeString($endTime);

            if (!$now->between($start, $end)) {
                return;
            }
        }

        $delay = (int) Setting::get('whatsapp_queue_delay', 5);
        // Calculate max messages we can safely process in 60 seconds (minus a tiny buffer)
        $limit = max(1, floor(55 / max(1, $delay)));

        $messages = DB::table('whatsapp_queue')
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        if (!$whatsapp->isConnected()) {
            $this->error('WhatsApp service is not connected.');
            return;
        }

        foreach ($messages as $msg) {
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
    }
}
