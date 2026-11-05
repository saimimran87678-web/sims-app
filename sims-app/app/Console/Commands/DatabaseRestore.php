<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class DatabaseRestore extends Command
{
    protected $signature = 'db:restore 
                            {file? : The backup file to restore (e.g. database_2026-11-01_09-08-55.sqlite)}
                            {--latest : Automatically select the latest available backup}
                            {--force : Force restore without interactive confirmation}';

    protected $description = 'Safely restore the SQLite database from a verified backup snapshot';

    public function handle(): int
    {
        $backupDir = config('database.backup_dir', storage_path('app/backups'));
        $databasePath = config('database.connections.sqlite.database', database_path('database.sqlite'));

        if (!is_dir($backupDir)) {
            $this->error("Backups directory does not exist at: {$backupDir}");
            return 1;
        }

        // 1. Resolve Target Backup File
        $fileName = $this->argument('file');
        if ($this->option('latest') || empty($fileName)) {
            $files = glob("{$backupDir}/database_*.sqlite");
            if (empty($files)) {
                $this->error("No backup files found in: {$backupDir}");
                return 1;
            }

            // Exclude pre-restore safety copies from --latest selection
            $files = array_filter($files, fn($f) => !str_starts_with(basename($f), 'database_pre_restore_'));
            if (empty($files)) {
                $this->error("No valid regular backups found in: {$backupDir}");
                return 1;
            }

            usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
            $targetPath = $files[0];
            $fileName = basename($targetPath);
        } else {
            $fileName = basename($fileName);
            $targetPath = "{$backupDir}/{$fileName}";
        }

        if (!file_exists($targetPath)) {
            $this->error("Specified backup file does not exist: {$targetPath}");
            return 1;
        }

        $this->info("Selected backup: {$fileName}");

        // 2. Interactive Confirmation Safeguard
        if (!$this->option('force')) {
            $this->warn("WARNING: Restoring will overwrite all current live data with the snapshot from: {$fileName}");
            if (!$this->confirm('Are you sure you wish to proceed with database restoration?')) {
                $this->comment('Restoration aborted by user.');
                return 0;
            }
        }

        // 3. Verify SHA-256 Checksum (if companion .sha256 file exists)
        $checksumFile = "{$targetPath}.sha256";
        if (file_exists($checksumFile)) {
            $this->line("Verifying SHA-256 checksum...");
            $storedHash = trim(explode(' ', file_get_contents($checksumFile))[0]);
            $actualHash = hash_file('sha256', $targetPath);

            if ($storedHash !== $actualHash) {
                $this->error("TAMPER DETECTED: Checksum verification failed!");
                $this->line("Expected: {$storedHash}");
                $this->line("Actual:   {$actualHash}");
                Log::critical("Database restore aborted due to SHA-256 checksum mismatch on {$fileName}");
                return 1;
            }
            $this->info("✓ SHA-256 Checksum verified.");
        }

        // 4. Verify Backup Integrity via PRAGMA integrity_check
        $this->line("Verifying backup SQLite file integrity...");
        if (!$this->verifyIntegrity($targetPath)) {
            $this->error("ABORTED: The target backup file is corrupted or invalid!");
            Log::critical("Database restore aborted: backup failed integrity check ({$fileName})");
            return 1;
        }
        $this->info("✓ Backup integrity verified.");

        // 5. Create a Pre-Restore Safety Snapshot of Current Database
        if (file_exists($databasePath)) {
            $safetyTimestamp = now()->format('Y-m-d_H-i-s');
            $safetyPath = "{$backupDir}/database_pre_restore_{$safetyTimestamp}.sqlite";
            $this->line("Creating pre-restore safety snapshot of live database...");

            try {
                if (file_exists($safetyPath)) {
                    @unlink($safetyPath);
                }
                $escapedSafety = str_replace("'", "''", $safetyPath);
                DB::connection('sqlite')->getPdo()->exec("VACUUM INTO '{$escapedSafety}';");
                $this->info("✓ Safety copy preserved at: database_pre_restore_{$safetyTimestamp}.sqlite");
            } catch (Throwable $e) {
                $this->warn("Notice: Live safety snapshot via VACUUM INTO was skipped (" . $e->getMessage() . "). Falling back to file copy.");
                @copy($databasePath, $safetyPath);
            }
        }

        // 6. Perform the Restore
        $this->line("Applying backup to live database...");
        try {
            // Disconnect active DB connections to release locks
            DB::disconnect();

            // Replace live database file
            if (!copy($targetPath, $databasePath)) {
                $this->error("Failed to copy backup file into destination {$databasePath}");
                return 1;
            }

            // Remove stale companion WAL files if present
            if (file_exists("{$databasePath}-wal")) {
                @unlink("{$databasePath}-wal");
            }
            if (file_exists("{$databasePath}-shm")) {
                @unlink("{$databasePath}-shm");
            }

            // Reconnect and verify live database integrity
            DB::reconnect();
            $liveCheck = DB::connection('sqlite')->select("PRAGMA integrity_check;");
            $status = $liveCheck[0]->integrity_check ?? null;

            if ($status !== 'ok') {
                $this->error("CRITICAL WARNING: Live database integrity check returned: {$status}");
                return 1;
            }

            // Clear framework application caches
            $this->callSilent('cache:clear');

            $this->info("✓ Database successfully restored from: {$fileName}");
            Log::info("Database successfully restored from {$fileName}");

            return 0;
        } catch (Throwable $e) {
            $this->error("Restore failed: " . $e->getMessage());
            Log::critical("Database restore failed", ['exception' => $e]);
            return 1;
        }
    }

    /**
     * Verifies the SQLite database integrity.
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
            return false;
        }
    }
}
