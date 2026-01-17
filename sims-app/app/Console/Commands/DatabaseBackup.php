<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup the SQLite database';

    public function handle()
    {
        $date = now()->format('Y-m-d_H-i-s');
        $databasePath = database_path('database.sqlite');
        $backupPath = storage_path("app/backups/database_{$date}.sqlite");
        $backupDir = dirname($backupPath);

        if (!file_exists($databasePath)) {
            $this->error("Database file not found at: {$databasePath}");
            return 1;
        }

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if (copy($databasePath, $backupPath)) {
            $this->info("Database successfully backed up to:");
            $this->line($backupPath);
            return 0;
        } else {
            $this->error("Failed to copy database file.");
            return 1;
        }
    }
}
