<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PDO;
use Throwable;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup 
                            {--retention= : Custom retention days for daily backups (default: 7)}
                            {--no-prune : Skip pruning older backups}';

    protected $description = 'Atomically backup the SQLite database using VACUUM INTO with integrity checks and checksums';

    public function handle(): int
    {
        $startTime = microtime(true);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $databasePath = config('database.connections.sqlite.database', database_path('database.sqlite'));
        $backupDir = config('database.backup_dir', storage_path('app/backups'));
        $backupFileName = "database_{$timestamp}.sqlite";
        $backupPath = "{$backupDir}/{$backupFileName}";

        $this->info("Initiating SQLite database backup...");

        $isMemory = ($databasePath === ':memory:');

        if (!$isMemory && !file_exists($databasePath)) {
            $this->error("Database file not found at: {$databasePath}");
            Log::error("Database backup aborted: database file missing at {$databasePath}");
            return 1;
        }

        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                $this->error("Failed to create backup directory: {$backupDir}");
                return 1;
            }
        }

        // SQLite VACUUM INTO will fail if the target file already exists
        if (file_exists($backupPath)) {
            @unlink($backupPath);
        }

        // 1. Execute Atomic SQLite Backup via VACUUM INTO
        $this->line("Creating atomic snapshot via VACUUM INTO...");
        try {
            $this->executeVacuumInto($backupPath);
        } catch (Throwable $e) {
            $this->error("VACUUM INTO failed: " . $e->getMessage());
            Log::critical("Database backup failed during VACUUM INTO", ['exception' => $e]);
            @unlink($backupPath);
            return 1;
        }

        if (!file_exists($backupPath) || filesize($backupPath) === 0) {
            $this->error("Backup creation failed: empty or missing output file.");
            @unlink($backupPath);
            return 1;
        }

        // 2. Perform Immediate Health Verification via PRAGMA integrity_check
        $this->line("Verifying backup integrity (PRAGMA integrity_check)...");
        if (!$this->verifyIntegrity($backupPath)) {
            $this->error("INTEGRITY CHECK FAILED: The backup file is corrupted!");
            Log::critical("Database backup failed integrity check. Corrupt file deleted: {$backupPath}");
            @unlink($backupPath);
            return 1;
        }

        // 3. Generate SHA-256 Checksum for Tamper and Bit-Rot Detection
        $sha256 = $this->generateChecksum($backupPath, $backupFileName);

        $duration = round(microtime(true) - $startTime, 2);
        $fileSize = $this->formatBytes(filesize($backupPath));

        $this->info("✓ Database backup completed successfully in {$duration}s!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Backup File', $backupFileName],
                ['Location', $backupPath],
                ['Size', $fileSize],
                ['SHA-256 Hash', substr($sha256, 0, 16) . '...' . substr($sha256, -16)],
                ['Integrity Check', 'PASSED (ok)'],
                ['Execution Mode', 'SQLite WAL Atomic Snapshot (VACUUM INTO)'],
            ]
        );

        Log::info("Database backup created successfully: {$backupFileName} ({$fileSize}) [SHA-256: {$sha256}]");

        // 4. Automated Retention Pruning (Grandfather-Father-Son rotation)
        if (!$this->option('no-prune')) {
            $dailyRetentionDays = (int) ($this->option('retention') ?: 7);
            $prunedCount = $this->pruneOldBackups($backupDir, $dailyRetentionDays);
            if ($prunedCount > 0) {
                $this->comment("Pruned {$prunedCount} old backup(s) according to GFS retention policy.");
            }
        }

        return 0;
    }

    /**
     * Executes the atomic VACUUM INTO statement through PDO.
     */
    protected function executeVacuumInto(string $targetPath): void
    {
        $escapedPath = str_replace("'", "''", $targetPath);
        $pdo = DB::connection('sqlite')->getPdo();
        $pdo->exec("VACUUM INTO '{$escapedPath}';");
    }

    /**
     * Verifies the standalone backup file by executing PRAGMA integrity_check.
     */
    protected function verifyIntegrity(string $backupPath): bool
    {
        try {
            $pdo = new PDO("sqlite:{$backupPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $statement = $pdo->query("PRAGMA integrity_check;");
            $result = $statement ? $statement->fetchColumn() : null;

            return $result === 'ok';
        } catch (Throwable $e) {
            Log::error("Integrity check execution error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Computes the SHA-256 hash and stores it in a companion .sha256 file.
     */
    protected function generateChecksum(string $backupPath, string $fileName): string
    {
        $hash = hash_file('sha256', $backupPath);
        $checksumFile = "{$backupPath}.sha256";
        file_put_contents($checksumFile, "{$hash}  {$fileName}\n");

        return $hash;
    }

    /**
     * Prunes old backups using Grandfather-Father-Son (GFS) retention:
     * - Keep last N daily backups (default 7 days).
     * - Keep 4 weekly backups (e.g. taken on Sundays for the last 4 weeks).
     * - Keep 12 monthly backups (e.g. taken on the 1st of each month for the last 12 months).
     * - Safely delete any older files and their .sha256 companions.
     */
    protected function pruneOldBackups(string $backupDir, int $dailyDays = 7): int
    {
        $files = glob("{$backupDir}/*.sqlite");
        if (!$files) {
            return 0;
        }

        $now = now();
        $prunedCount = 0;

        foreach ($files as $filePath) {
            $fileName = basename($filePath);

            // Skip pre-restore safety snapshots from auto-pruning
            if (str_starts_with($fileName, 'database_pre_restore_')) {
                continue;
            }

            // Extract date from standard pattern: database_YYYY-MM-DD_HH-mm-ss.sqlite
            if (!preg_match('/database_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sqlite$/', $fileName, $matches)) {
                continue;
            }

            try {
                $fileDate = Carbon::createFromFormat('Y-m-d_H-i-s', $matches[1]);
            } catch (Throwable) {
                continue;
            }

            $ageDays = $fileDate->diffInDays($now);

            // Policy Rule 1: Keep all backups within the daily retention window
            if ($ageDays <= $dailyDays) {
                continue;
            }

            // Policy Rule 2: Keep weekly backups (Sunday) for up to 28 days (4 weeks)
            if ($ageDays <= 28 && $fileDate->isSunday()) {
                continue;
            }

            // Policy Rule 3: Keep monthly backups (1st day of month) for up to 365 days (12 months)
            if ($ageDays <= 365 && $fileDate->day === 1) {
                continue;
            }

            // Exceeded retention policy: prune file and its companion checksum
            @unlink($filePath);
            if (file_exists("{$filePath}.sha256")) {
                @unlink("{$filePath}.sha256");
            }
            $prunedCount++;
        }

        return $prunedCount;
    }

    /**
     * Formats bytes into human-readable representation.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
