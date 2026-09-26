<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupWindows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sims:setup-windows 
                            {--uninstall : Remove registered SIMS background services from Windows Task Scheduler}
                            {--dry-run : Print the commands that would be executed without running them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register and manage SIMS background services in Windows Task Scheduler (FrankenPHP, Queue, Scheduler)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('      🪟 SIMS Windows Task Scheduler Supervisor      ');
        $this->info('====================================================');

        $isWindows = PHP_OS_FAMILY === 'Windows';
        $dryRun = $this->option('dry-run');

        if (!$isWindows && !$dryRun) {
            $this->warn('⚠️ Notice: Current operating system is not Windows.');
            $this->line('You are running on Linux/macOS. Use --dry-run to preview the Windows setup commands.');
            $this->line('For Linux environments, run: sudo php artisan sims:setup-linux');
            return 1;
        }

        $appPath = base_path();

        // 1. Resolve PHP binary path
        $bundledPhp = dirname($appPath) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
        $localBundledPhp = $appPath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';

        if (file_exists($bundledPhp)) {
            $phpBinary = $bundledPhp;
        } elseif (file_exists($localBundledPhp)) {
            $phpBinary = $localBundledPhp;
        } else {
            $phpBinary = $isWindows ? PHP_BINARY : 'C:\\php\\php.exe';
        }

        // 2. Resolve FrankenPHP binary path
        $bundledFranken = dirname($appPath) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'frankenphp.exe';
        $localBundledFranken = $appPath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'frankenphp.exe';

        if (file_exists($bundledFranken)) {
            $frankenBinary = $bundledFranken;
        } elseif (file_exists($localBundledFranken)) {
            $frankenBinary = $localBundledFranken;
        } else {
            $frankenBinary = 'frankenphp.exe';
        }

        $publicPath = $appPath . DIRECTORY_SEPARATOR . 'public';
        $artisanPath = $appPath . DIRECTORY_SEPARATOR . 'artisan';

        $rootDir = dirname($appPath);
        $servicesDir = $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'windows' . DIRECTORY_SEPARATOR . 'services';
        $binDir = $rootDir . DIRECTORY_SEPARATOR . 'bin';
        $activeRunnersDir = is_dir($servicesDir) ? $servicesDir : $binDir;

        // Ensure wrapper batch files exist in active runners directory
        $webBat = $activeRunnersDir . DIRECTORY_SEPARATOR . 'run-web.bat';
        $queueBat = $activeRunnersDir . DIRECTORY_SEPARATOR . 'run-queue.bat';
        $schedulerBat = $activeRunnersDir . DIRECTORY_SEPARATOR . 'run-scheduler.bat';

        if (!file_exists($webBat)) {
            // IMPORTANT: Must cd into APP_DIR first so that Caddyfile's "root * public" resolves
            // to sims-app\public correctly. No --adapter flag needed; FrankenPHP auto-detects format.
            file_put_contents($webBat,
                "@echo off\r\n" .
                "cd /d \"{$appPath}\"\r\n" .
                "set \"FRANKEN={$frankenBinary}\"\r\n" .
                "set \"PHP_FCGI_MAX_REQUESTS=0\"\r\n" .
                "if not exist \"%FRANKEN%\" set \"FRANKEN=frankenphp.exe\"\r\n" .
                "\"%FRANKEN%\" run --config \"{$appPath}\\Caddyfile\" >> \"{$appPath}\\storage\\logs\\web-server.log\" 2>&1\r\n"
            );
        }
        if (!file_exists($queueBat)) {
            file_put_contents($queueBat,
                "@echo off\r\n" .
                "cd /d \"{$appPath}\"\r\n" .
                "\"{$phpBinary}\" \"{$artisanPath}\" queue:work --sleep=3 --tries=3 --max-time=3600\r\n"
            );
        }
        if (!file_exists($schedulerBat)) {
            file_put_contents($schedulerBat,
                "@echo off\r\n" .
                "cd /d \"{$appPath}\"\r\n" .
                "\"{$phpBinary}\" \"{$artisanPath}\" schedule:work\r\n"
            );
        }

        $services = [
            'SIMS-Web' => [
                'desc'   => 'SIMS Web Server (FrankenPHP HTTPS & HTTP)',
                'script' => $webBat,
            ],
            'SIMS-Queue' => [
                'desc'   => 'SIMS Background Queue Worker',
                'script' => $queueBat,
            ],
            'SIMS-Scheduler' => [
                'desc'   => 'SIMS Task Scheduler (Updates & License Sync)',
                'script' => $schedulerBat,
            ],
        ];

        // ── Handle Uninstall ──────────────────────────────────────────────
        if ($this->option('uninstall')) {
            $this->warn('🛑 Removing SIMS background services from Windows Task Scheduler...');

            foreach (array_keys($services) as $taskName) {
                $delCmd = "schtasks /delete /tn \"{$taskName}\" /f";
                if ($dryRun) {
                    $this->line("[DRY RUN] {$delCmd}");
                } else {
                    exec("{$delCmd} 2>nul", $output, $returnCode);
                    $this->line("   - Removed task: {$taskName}");
                }
            }

            $this->info('✅ All SIMS Windows background tasks removed.');
            return 0;
        }

        // ── Install Services via schtasks ─────────────────────────────────
        $this->info('⚙️ Registering 3 background tasks with Windows Task Scheduler (ONSTART)...');
        $this->line("   PHP Binary:        {$phpBinary}");
        $this->line("   FrankenPHP Binary: {$frankenBinary}");
        $this->line("   Working Directory: {$appPath}");
        $this->line("   Runners Directory: {$activeRunnersDir}");
        $this->line('');

        $successCount = 0;

        foreach ($services as $taskName => $config) {
            $scriptPath = $config['script'];
            
            // Create the scheduled task:
            // /SC ONSTART : Run when Windows starts
            // /RU SYSTEM  : Run headless as Local System account (survives user logoff)
            // /RL HIGHEST : Run with elevated privileges
            // /F          : Overwrite existing task definition
            $createCmd = "schtasks /create /tn \"{$taskName}\" /tr \"\\\"{$scriptPath}\\\"\" /sc ONSTART /ru SYSTEM /rl HIGHEST /f";

            if ($dryRun) {
                $this->info("[DRY RUN] Create {$taskName}:");
                $this->line("   Command: {$createCmd}");
                $this->line("   Trigger: schtasks /run /tn \"{$taskName}\"");
                $successCount++;
            } else {
                exec("{$createCmd} 2>&1", $output, $returnCode);

                // If /ru SYSTEM fails on some Windows editions, retry without /ru SYSTEM
                if ($returnCode !== 0) {
                    $fallbackCmd = "schtasks /create /tn \"{$taskName}\" /tr \"\\\"{$scriptPath}\\\"\" /sc ONSTART /rl HIGHEST /f";
                    exec("{$fallbackCmd} 2>&1", $output, $returnCode);
                }

                if ($returnCode === 0) {
                    // Start immediately
                    exec("schtasks /run /tn \"{$taskName}\" 2>nul");
                    $this->info("   ✅ Registered & Started: {$taskName} ({$config['desc']})");
                    $successCount++;
                } else {
                    $this->error("   ❌ Failed to register: {$taskName}");
                    if (!empty($output)) {
                        $this->line('      ' . implode("\n      ", $output));
                    }
                }
                $output = [];
            }
        }

        if ($successCount === count($services)) {
            $this->info('');
            $this->info('====================================================');
            $this->info('🎉 All SIMS Windows Services are ACTIVE & PERSISTENT');
            $this->info('====================================================');
            $this->line('• Auto-starts automatically on Windows boot (SYSTEM account).');
            $this->line('• Survives administrator logoff.');
            $this->line('• Access application in browser at:');
            $hostname = gethostname();
            $this->info("   🔒 https://sims.local");
            $this->info("   🔒 https://localhost");
            $this->line("   🌐 Local LAN: https://{$hostname}.local  or  http://{$hostname}");
            return 0;
        }

        $this->warn('⚠️ Some tasks could not be registered. Ensure you are running Command Prompt as Administrator.');
        return 1;
    }
}
