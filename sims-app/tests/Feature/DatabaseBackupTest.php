<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PDO;

class DatabaseBackupTest extends TestCase
{
    protected string $backupDir;
    protected string $testDbPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = storage_path('framework/testing/backups');
        config(['database.backup_dir' => $this->backupDir]);
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $this->testDbPath = storage_path('framework/testing/test_backup_suite.sqlite');
        if (!is_dir(dirname($this->testDbPath))) {
            mkdir(dirname($this->testDbPath), 0755, true);
        }

        if (file_exists($this->testDbPath)) {
            @unlink($this->testDbPath);
        }

        $pdo = new PDO("sqlite:{$this->testDbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE test_data (id INTEGER PRIMARY KEY, name TEXT);");
        $pdo->exec("INSERT INTO test_data (name) VALUES ('Backup Test Row');");

        config(['database.connections.sqlite.database' => $this->testDbPath]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    protected function tearDown(): void
    {
        DB::disconnect();
        if (file_exists($this->testDbPath)) {
            @unlink($this->testDbPath);
        }
        if (is_dir($this->backupDir)) {
            $files = glob("{$this->backupDir}/*");
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        parent::tearDown();
    }

    public function test_db_backup_command_creates_valid_sqlite_backup_and_checksum(): void
    {
        $exitCode = Artisan::call('db:backup', ['--no-prune' => true]);
        $this->assertSame(0, $exitCode);

        // Find the most recent backup
        $files = glob("{$this->backupDir}/database_*.sqlite");
        $this->assertNotEmpty($files);

        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $latestBackup = $files[0];

        $this->assertFileExists($latestBackup);
        $this->assertGreaterThan(0, filesize($latestBackup));

        // Verify SQLite PRAGMA integrity_check
        $pdo = new PDO("sqlite:{$latestBackup}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $integrity = $pdo->query("PRAGMA integrity_check;")->fetchColumn();
        $this->assertSame('ok', $integrity);

        // Verify SHA-256 checksum file
        $checksumFile = "{$latestBackup}.sha256";
        $this->assertFileExists($checksumFile);

        $storedChecksum = trim(explode(' ', file_get_contents($checksumFile))[0]);
        $actualChecksum = hash_file('sha256', $latestBackup);
        $this->assertSame($actualChecksum, $storedChecksum);
    }

    public function test_db_backup_prunes_backups_exceeding_retention_policy(): void
    {
        // Create simulated old backup (e.g. 40 days old, not Sunday, not day 1)
        $oldDate = now()->subDays(40)->format('Y-m-d_H-i-s');
        $oldBackupPath = "{$this->backupDir}/database_{$oldDate}.sqlite";
        touch($oldBackupPath);
        file_put_contents("{$oldBackupPath}.sha256", "dummyhash  database_{$oldDate}.sqlite\n");

        $this->assertFileExists($oldBackupPath);
        $this->assertFileExists("{$oldBackupPath}.sha256");

        // Run backup with default pruning
        $exitCode = Artisan::call('db:backup');
        $this->assertSame(0, $exitCode);

        // The 40-day old non-Sunday non-1st backup should be pruned
        $this->assertFileDoesNotExist($oldBackupPath);
        $this->assertFileDoesNotExist("{$oldBackupPath}.sha256");
    }

    public function test_db_restore_command_restores_from_latest_backup_cleanly(): void
    {
        // 1. Create a fresh verified backup
        Artisan::call('db:backup', ['--no-prune' => true]);

        // 2. Perform restore with --latest and --force
        $exitCode = Artisan::call('db:restore', [
            '--latest' => true,
            '--force'  => true,
        ]);

        $this->assertSame(0, $exitCode);

        // 3. Verify live DB integrity after restore
        $liveCheck = DB::connection('sqlite')->select("PRAGMA integrity_check;");
        $status = $liveCheck[0]->integrity_check ?? null;
        $this->assertSame('ok', $status);
    }

    public function test_db_restore_aborts_if_checksum_mismatch_detected(): void
    {
        // 1. Create a fresh backup
        Artisan::call('db:backup', ['--no-prune' => true]);
        $files = glob("{$this->backupDir}/database_*.sqlite");
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $latestBackup = $files[0];
        $checksumFile = "{$latestBackup}.sha256";

        // 2. Tamper with the checksum file
        file_put_contents($checksumFile, "tampered_fake_hash_12345  " . basename($latestBackup) . "\n");

        // 3. Attempt restore - should be rejected with code 1
        $exitCode = Artisan::call('db:restore', [
            'file'    => basename($latestBackup),
            '--force' => true,
        ]);

        $this->assertSame(1, $exitCode);
    }
}
