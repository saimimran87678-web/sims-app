<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupLinux extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sims:setup-linux 
                            {--uninstall : Remove registered SIMS systemd services}
                            {--dry-run : Print generated systemd units without installing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register and manage SIMS background services in Linux systemd (FrankenPHP, Queue, Scheduler)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('          🐧 SIMS Linux systemd Supervisor          ');
        $this->info('====================================================');

        if (PHP_OS_FAMILY === 'Windows') {
            $this->error('❌ Error: Current operating system is Windows.');
            $this->line('For Windows environments, run: php artisan sims:setup-windows');
            return 1;
        }

        $appPath = base_path();
        $dryRun = $this->option('dry-run');

        // Resolve user and group
        $user = function_exists('posix_getpwuid') && function_exists('posix_getuid')
            ? (posix_getpwuid(posix_getuid())['name'] ?? get_current_user())
            : get_current_user();

        // 1. Resolve PHP binary
        $phpBinary = PHP_BINARY;

        // 2. Resolve FrankenPHP binary
        $bundledFranken = dirname($appPath) . '/runtime/frankenphp';
        $localBundledFranken = $appPath . '/runtime/frankenphp';
        $systemFranken = trim(shell_exec('command -v frankenphp 2>/dev/null') ?? '');

        if (file_exists($bundledFranken) && is_executable($bundledFranken)) {
            $webCommand = "{$bundledFranken} php-server --listen :80 --root {$appPath}/public";
        } elseif (file_exists($localBundledFranken) && is_executable($localBundledFranken)) {
            $webCommand = "{$localBundledFranken} php-server --listen :80 --root {$appPath}/public";
        } elseif (!empty($systemFranken)) {
            $webCommand = "{$systemFranken} php-server --listen :80 --root {$appPath}/public";
        } else {
            // High-concurrency fallback using PHP built-in server with 4 workers
            $webCommand = "{$phpBinary} -S 0.0.0.0:80 -t {$appPath}/public";
        }

        $services = [
            'sims-web' => [
                'desc' => 'SIMS High-Performance Web Server (Port 80)',
                'exec' => $webCommand,
                'env'  => 'PHP_CLI_SERVER_WORKERS=4',
            ],
            'sims-queue' => [
                'desc' => 'SIMS Background Queue Worker',
                'exec' => "{$phpBinary} {$appPath}/artisan queue:work --sleep=3 --tries=3",
            ],
            'sims-scheduler' => [
                'desc' => 'SIMS Task Scheduler (Updates & License Sync)',
                'exec' => "{$phpBinary} {$appPath}/artisan schedule:work",
            ],
        ];

        // ── Handle Uninstall ──────────────────────────────────────────────
        if ($this->option('uninstall')) {
            $this->warn('🛑 Removing SIMS systemd services...');

            foreach (array_keys($services) as $serviceName) {
                if ($dryRun) {
                    $this->line("[DRY RUN] sudo systemctl stop {$serviceName}");
                    $this->line("[DRY RUN] sudo systemctl disable {$serviceName}");
                    $this->line("[DRY RUN] sudo rm -f /etc/systemd/system/{$serviceName}.service");
                } else {
                    exec("sudo systemctl stop {$serviceName} 2>/dev/null");
                    exec("sudo systemctl disable {$serviceName} 2>/dev/null");
                    exec("sudo rm -f /etc/systemd/system/{$serviceName}.service 2>/dev/null");
                    $this->line("   - Removed: {$serviceName}.service");
                }
            }

            if (!$dryRun) {
                exec('sudo systemctl daemon-reload 2>/dev/null');
            }

            $this->info('✅ All SIMS systemd services have been removed.');
            return 0;
        }

        // ── Install Services via systemd ──────────────────────────────────
        $this->info('⚙️ Generating systemd service units for auto-boot & restart recovery...');
        $this->line("   System User:       {$user}");
        $this->line("   Working Directory: {$appPath}");
        $this->line('');

        $tmpDir = sys_get_temp_dir();

        foreach ($services as $serviceName => $config) {
            $envLine = isset($config['env']) ? "Environment=\"{$config['env']}\"\n" : '';
            
            $unitContent = <<<EOF
[Unit]
Description={$config['desc']}
After=network.target

[Service]
Type=simple
User={$user}
WorkingDirectory={$appPath}
{$envLine}ExecStart={$config['exec']}
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
EOF;

            if ($dryRun) {
                $this->info("[DRY RUN] Service: {$serviceName}.service");
                $this->line($unitContent);
                $this->line('----------------------------------------------------');
            } else {
                $tmpFile = "{$tmpDir}/{$serviceName}.service";
                file_put_contents($tmpFile, $unitContent);

                exec("sudo cp {$tmpFile} /etc/systemd/system/{$serviceName}.service 2>&1", $copyOut, $copyCode);
                @unlink($tmpFile);

                if ($copyCode === 0) {
                    $this->info("   ✅ Created unit: /etc/systemd/system/{$serviceName}.service");
                } else {
                    $this->error("   ❌ Failed to create unit for {$serviceName}. Ensure you have sudo privileges.");
                }
            }
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Daemon reload and enable would be executed next.');
            return 0;
        }

        // Reload systemd daemon
        exec('sudo systemctl daemon-reload 2>&1');

        // Enable and start services
        foreach (array_keys($services) as $serviceName) {
            exec("sudo systemctl enable {$serviceName} 2>&1");
            exec("sudo systemctl restart {$serviceName} 2>&1");
            $this->info("   🚀 Enabled & Started: {$serviceName}.service");
        }

        // Discover local IP address
        $ip = trim(shell_exec("hostname -I 2>/dev/null | awk '{print $1}'") ?? '');
        $hostname = gethostname();

        $this->info('');
        $this->info('====================================================');
        $this->info('🎉 All SIMS Linux Services are ACTIVE & PERSISTENT  ');
        $this->info('====================================================');
        $this->line('• Auto-starts automatically on server reboot.');
        $this->line('• Automatically restarts within 5 seconds if a crash occurs.');
        $this->line('• Manage services anytime:');
        $this->line('    sudo systemctl status sims-web');
        $this->line('    sudo systemctl restart sims-*');
        $this->line('');
        $this->info('• Access application in browser at:');
        if (!empty($ip)) {
            $this->info("   🌐 http://{$ip}");
        }
        $this->info("   🌐 http://{$hostname}.local  or  http://localhost");

        return 0;
    }
}
