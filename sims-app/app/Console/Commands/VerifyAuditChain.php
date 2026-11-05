<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityLog;
use Carbon\Carbon;

class VerifyAuditChain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:verify-chain {--limit= : Limit the number of records to verify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cryptographically verify the HMAC-SHA256 tamper-evident hash chain of activity logs';

    /**
     * Command aliases.
     *
     * @var array
     */
    protected $aliases = ['audit:verify'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Starting cryptographic audit chain verification...");

        $query = ActivityLog::orderBy('id', 'asc');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $logs = $query->get();
        $total = $logs->count();

        if ($total === 0) {
            $this->info("No activity logs found in database. Audit chain is empty.");
            return 0;
        }

        $expectedPreviousHash = str_repeat('0', 64);
        $verifiedCount = 0;

        foreach ($logs as $index => $log) {
            // 1. Verify hash linkage to previous record
            if ($log->previous_hash !== $expectedPreviousHash) {
                $this->error("\n[TAMPER DETECTED] Broken chain linkage at Log #{$log->id}!");
                $this->line("  Expected previous hash: {$expectedPreviousHash}");
                $this->line("  Found previous hash:    {$log->previous_hash}");
                $this->line("  Timestamp:              {$log->created_at}");
                $this->line("  Action:                 {$log->action}");
                $this->line("  Module:                 {$log->module}");
                return 1;
            }

            // 2. Re-calculate cryptographic HMAC signature
            $calculatedHash = ActivityLog::computeRecordHash(
                $log->user_id,
                $log->action,
                $log->module,
                $log->model_type,
                $log->model_id,
                $log->description,
                $log->created_at,
                $log->previous_hash
            );

            if ($log->record_hash !== $calculatedHash) {
                $this->error("\n[TAMPER DETECTED] Cryptographic signature mismatch at Log #{$log->id}!");
                $this->line("  Stored record hash:     {$log->record_hash}");
                $this->line("  Recalculated hash:      {$calculatedHash}");
                $this->line("  Description:            {$log->description}");
                $this->line("  Timestamp:              {$log->created_at}");
                return 1;
            }

            $expectedPreviousHash = $log->record_hash;
            $verifiedCount++;
        }

        $this->newLine();
        $this->info("✓ AUDIT INTEGRITY CONFIRMED");
        $this->line("  Successfully verified {$verifiedCount} chained activity log records.");
        $this->line("  Zero sequence gaps, tampering, or cryptographic mismatches detected.");

        return 0;
    }
}
