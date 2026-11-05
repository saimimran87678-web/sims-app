<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeWhatsAppQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:purge-queue {--days=30 : Number of days of processed message history to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge processed (sent and failed) messages from whatsapp_queue older than the retention threshold to protect student PII';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: 30);
        $cutoff = now()->subDays($days);

        $this->info("Purging WhatsApp queue messages older than {$days} days (Cutoff: {$cutoff->toDateTimeString()})...");

        $deletedCount = DB::table('whatsapp_queue')
            ->whereIn('status', ['sent', 'failed'])
            ->where('created_at', '<=', $cutoff)
            ->delete();

        $this->info("Successfully purged {$deletedCount} historical message(s) from whatsapp_queue.");
        Log::info("whatsapp:purge-queue purged {$deletedCount} records older than {$days} days.");

        return 0;
    }
}
