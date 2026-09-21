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

        $services = [
            'SIMS-Web' => [
                'desc' => 'SIMS Web Server (FrankenPHP on Port 80)',
                'cmd'  => "\"{$frankenBinary}\" php-server --listen :80 --root \"{$publicPath}\"",
            ],
            'SIMS-Queue' => [
                'desc' => 'SIMS Background Queue Worker',
                'cmd'  => "\"{$phpBinary}\" \"{$artisanPath}\" queue:work --sleep=3 --tries=3",
            ],
            'SIMS-Scheduler' => [
                'desc' => 'SIMS Task Scheduler (Updates & License Sync)',
                'cmd'  => "\"{$phpBinary}\" \"{$artisanPath}\" schedule:work",
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
        $this->info('⚙️ Registering 3 background tasks with Windows Task Scheduler (ONSTART / SYSTEM)...');
        $this->line("   PHP Binary:        {$phpBinary}");
        $this->line("   FrankenPHP Binary: {$frankenBinary}");
        $this->line("   Working Directory: {$appPath}");
        $this->line('');

        $successCount = 0;

        foreach ($services as $taskName => $config) {
            $cmdLine = $config['cmd'];
            
            // Create the scheduled task:
            // /SC ONSTART : Run when Windows starts
            // /RU SYSTEM  : Run headless as Local System account (survives user logoff)
            // /RL HIGHEST : Run with elevated privileges
            // /F          : Overwrite existing task definition
            $createCmd = "schtasks /create /tn \"{$taskName}\" /tr \"{$cmdLine}\" /sc ONSTART /ru SYSTEM /rl HIGHEST /f";

            if ($dryRun) {
                $this->info("[DRY RUN] Create {$taskName}:");
                $this->line("   Command: {$createCmd}");
                $this->line("   Trigger: schtasks /run /tn \"{$taskName}\"");
                $successCount++;
            } else {
                exec("{$createCmd} 2>&1", $output, $returnCode);

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
            $this->info("   🌐 http://{$hostname}  or  http://localhost");
            return 0;
        }

        $this->warn('⚠️ Some tasks could not be registered. Ensure you are running Command Prompt as Administrator.');
        return 1;
    }
}
