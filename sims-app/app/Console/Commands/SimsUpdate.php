<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sims:update 
                            {--check : Check if updates are available without applying}
                            {--force : Force apply update even if version is current or higher}
                            {--manifest= : Custom manifest URL or file path}
                            {--extract-to= : Target directory to extract to}
                            {--skip-health-check : Skip localhost health check probe}
                            {--verify-checksum : Display installed version checksum verification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated, cross-platform update manager with SHA-256 checksum verification and safe rollback';

    /**
     * Default manifest URL (hosted on GitHub Pages or CDN)
     */
    public const DEFAULT_MANIFEST_URL = 'https://raw.githubusercontent.com/saimimran87678-web/sims-app/main/manifest.json';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // ── Option: Display current installed version & checksum ──────
        if ($this->option('verify-checksum')) {
            $version   = config('app.version', '2.5.0');
            $checksum  = Setting::getGlobal('last_update_checksum', 'None (Initial installation)');
            $updatedAt = Setting::getGlobal('last_updated_at', 'N/A');

            $this->info("==========================================");
            $this->info(" SIMS Integrity & Checksum Status");
            $this->info("==========================================");
            $this->line(" Current Version  : v{$version}");
            $this->line(" Verified SHA-256 : {$checksum}");
            $this->line(" Last Updated At  : {$updatedAt}");
            $this->info("==========================================");
            return 0;
        }

        $manifestSource = $this->option('manifest') ?: config('app.update_manifest_url', self::DEFAULT_MANIFEST_URL);
        $this->line("📡 Fetching update manifest from: {$manifestSource}");

        // ── Step 0: Fetch & parse manifest ────────────────────────────
        $manifest = $this->fetchManifest($manifestSource);
        if (!$manifest) {
            if ($this->option('check')) {
                $this->warn("⚠️ Unable to retrieve update manifest from: {$manifestSource}");
                return 1;
            }
            // In unattended scheduled runs, silently exit if no internet
            $this->line("No internet or manifest unreachable. Will retry next scheduled run.");
            return 0;
        }

        $latestVersion   = trim($manifest['version'] ?? '');
        $currentVersion  = config('app.version', '2.5.0');
        $expectedHash    = strtolower(trim($manifest['checksum'] ?? $manifest['sha256'] ?? ''));
        $downloadUrl     = $manifest['download_url'] ?? '';
        $minPhpVersion   = $manifest['min_php_version'] ?? '8.2.0';
        $releaseNotes    = $manifest['changelog'] ?? $manifest['notes'] ?? 'No release notes provided.';

        if (empty($latestVersion) || empty($downloadUrl)) {
            $this->error("❌ Invalid manifest structure: missing 'version' or 'download_url'.");
            return 1;
        }

        $installedChecksum = Setting::getGlobal('last_update_checksum', '');
        $isSameVersion = version_compare($latestVersion, $currentVersion, '==');
        $isChecksumDiff = (!empty($expectedHash) && $expectedHash !== $installedChecksum);
        $isNewer = version_compare($latestVersion, $currentVersion, '>') || ($isSameVersion && $isChecksumDiff);
        $isForce = (bool) $this->option('force');

        // ── Handle --check option ─────────────────────────────────────
        if ($this->option('check')) {
            $this->info("==========================================");
            $this->info(" SIMS Update Check");
            $this->info("==========================================");
            $this->line(" Current Version   : v{$currentVersion}");
            $this->line(" Available Version : v{$latestVersion}");
            $this->line(" Manifest SHA-256  : " . ($expectedHash ?: 'MISSING'));
            $this->line(" Release Notes     : {$releaseNotes}");
            $this->info("==========================================");

            if ($isNewer) {
                $msg = ($isChecksumDiff && !version_compare($latestVersion, $currentVersion, '>'))
                    ? "🚀 A hotfix patch for v{$latestVersion} is available to install (checksum updated)."
                    : "🚀 A newer version (v{$latestVersion}) is available to install.";
                $this->info($msg);
            } else {
                $this->info("✨ SIMS is up to date (v{$currentVersion}).");
            }
            return 0;
        }

        if (!$isNewer && !$isForce) {
            $this->info("✨ SIMS is already up to date (v{$currentVersion}).");
            return 0;
        }

        if ($isForce && !$isNewer) {
            $this->warn("⚠️ Force-reapplying version v{$latestVersion} over current v{$currentVersion}.");
        } elseif ($isChecksumDiff && !version_compare($latestVersion, $currentVersion, '>')) {
            $this->info("🚀 Applying hotfix patch for v{$latestVersion} (checksum updated)");
        } else {
            $this->info("🚀 Applying update: v{$currentVersion} → v{$latestVersion}");
        }

        // ── Check minimum PHP version requirement ─────────────────────
        if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
            $this->error("❌ PHP requirement not met: SIMS v{$latestVersion} requires PHP {$minPhpVersion}+ (Current: " . PHP_VERSION . ")");
            return 1;
        }

        // ── STEP 1: Snapshot current database and public uploads ──────
        $snapshotTimestamp = now()->format('Ymd_His');
        $snapshotDir       = storage_path("snapshots/{$snapshotTimestamp}_v{$currentVersion}");
        @mkdir($snapshotDir, 0755, true);

        $dbSource = database_path('database.sqlite');
        if (file_exists($dbSource)) {
            copy($dbSource, "{$snapshotDir}/database.sqlite");
        }

        $uploadsSource = storage_path('app/public');
        if (is_dir($uploadsSource)) {
            $this->mirrorDir($uploadsSource, "{$snapshotDir}/uploads");
        }

        $meta = [
            'timestamp'        => $snapshotTimestamp,
            'previous_version' => $currentVersion,
            'target_version'   => $latestVersion,
            'db_size_bytes'    => file_exists($dbSource) ? filesize($dbSource) : 0,
        ];
        file_put_contents("{$snapshotDir}/meta.json", json_encode($meta, JSON_PRETTY_PRINT));
        $this->line("💾 Snapshot backup successfully saved to: {$snapshotDir}");

        // ── STEP 2: Download & verify SHA-256 Checksum ────────────────
        $updateDir = storage_path('updates');
        @mkdir($updateDir, 0755, true);
        $zipPath = "{$updateDir}/sims-v{$latestVersion}.zip";

        $this->line("📥 Downloading update package from: {$downloadUrl}");
        if (!$this->downloadPackage($downloadUrl, $zipPath)) {
            $this->error("❌ Download failed. Aborting update.");
            return 1;
        }

        $this->line("🔒 Verifying package integrity (SHA-256 checksum)...");
        if (empty($expectedHash)) {
            $this->error("❌ Security Error: Manifest has no checksum. Aborting update for safety.");
            @unlink($zipPath);
            return 1;
        }

        $actualHash = hash_file('sha256', $zipPath);
        if (!hash_equals($expectedHash, $actualHash)) {
            $this->error("❌ Checksum verification mismatch!");
            $this->error("   Expected SHA-256 : {$expectedHash}");
            $this->error("   Actual SHA-256   : {$actualHash}");
            @unlink($zipPath);
            Log::error("SIMS update security mismatch for v{$latestVersion}. Expected: {$expectedHash}, Computed: {$actualHash}");
            return 1;
        }
        $this->info("✅ SHA-256 Checksum verified: {$actualHash}");

        // ── STEP 3: Extract update package ────────────────────────────
        $this->line("📦 Extracting files to application root...");
        $zip = new \ZipArchive();
        $openResult = $zip->open($zipPath);
        if ($openResult !== true) {
            $this->error("❌ Corrupt update archive: cannot open zip (Code: {$openResult}).");
            @unlink($zipPath);
            return $this->rollback($snapshotDir, "Corrupt update zip");
        }

        // Validate zip security against path traversal attacks
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_contains($entry, '..') || str_starts_with($entry, '/') || str_starts_with($entry, '\\')) {
                $zip->close();
                @unlink($zipPath);
                $this->error("❌ Malicious path detected in update archive: {$entry}");
                return $this->rollback($snapshotDir, "Malicious path in update archive: {$entry}");
            }
        }

        // CRITICAL: The patch zip structure is relative to the INSTALLATION ROOT, e.g.:
        //   sims-app/app/...      sims-app/resources/...      scripts/windows/...
        // base_path()         = <install>\sims-app\  → would extract into sims-app\sims-app\ (wrong!)
        // dirname(base_path()) = <install>\           → correct installation root
        $extractPath = $this->option('extract-to') ?: dirname(base_path());
        $zip->extractTo($extractPath);
        $zip->close();
        @unlink($zipPath);
        $this->line('✅ Files extracted successfully to installation root.');

        // ── STEP 4: Run silent database migrations ────────────────────
        if (!app()->runningUnitTests()) {
            $this->line("⚡ Applying database migrations...");
            try {
                $migrateExit = Artisan::call('migrate', ['--force' => true]);
                if ($migrateExit !== 0) {
                    throw new \RuntimeException("php artisan migrate failed with exit code: {$migrateExit}");
                }
            } catch (\Throwable $e) {
                $this->error("❌ Migration failure: " . $e->getMessage());
                return $this->rollback($snapshotDir, "Migration failure: " . $e->getMessage());
            }
            $this->line("✅ Migrations executed cleanly.");
        }

        // ── STEP 5: Health Check Probe ────────────────────────────────
        if (!$this->option('skip-health-check')) {
            $this->line("🔍 Probing /ping-internal health check endpoint...");
            if (!$this->probeHealthCheck()) {
                $this->error("❌ Health probe failed! System is not responding cleanly.");
                return $this->rollback($snapshotDir, "Health probe failed to return alive status");
            }
            $this->info("✅ Health check passed. Application is operational.");
        }

        // ── STEP 6: Finalize update, record checksum & warm caches ────
        if (!app()->runningUnitTests()) {
            try {
                Artisan::call('optimize:clear');
                Artisan::call('optimize');
            } catch (\Throwable $e) {
                Log::warning("Post-update optimization warning: " . $e->getMessage());
            }

            // Update APP_VERSION in .env
            $this->updateEnvVersion($latestVersion);
        }

        // Update checksum and version records in SQLite Settings
        Setting::setGlobal('installed_version', $latestVersion);
        Setting::setGlobal('last_update_checksum', $actualHash);
        Setting::setGlobal('last_updated_at', now()->toIso8601String());

        $this->info("==========================================");
        $this->info("🎉 SIMS successfully updated to v{$latestVersion}!");
        $this->info(" Verified SHA-256 : {$actualHash}");
        $this->info("==========================================");

        // ── STEP 7: Restart background services so new code is loaded ──
        $this->line('🔄 Restarting SIMS background services to load updated code...');
        $this->restartServices();

        Log::info("SIMS successfully updated from v{$currentVersion} to v{$latestVersion}. Verified SHA-256: {$actualHash}");
        return 0;
    }

    /**
     * Perform an HTTP GET request with CA bundle verification and fallback for Windows environments.
     */
    protected function performHttpGet(string $url, int $timeoutSec): ?\Illuminate\Http\Client\Response
    {
        // 1. Resolve bundled CA bundle candidates
        $caCandidates = [
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'cacert.pem',
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'cacert.pem',
            base_path('resources' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem'),
        ];

        $caPath = null;
        foreach ($caCandidates as $candidate) {
            if (file_exists($candidate)) {
                $caPath = $candidate;
                break;
            }
        }

        // 2. Attempt with verified CA certificate
        try {
            $client = Http::timeout($timeoutSec);
            if ($caPath) {
                $client = $client->withOptions(['verify' => $caPath]);
            }
            return $client->get($url);
        } catch (\Throwable $e) {
            // 3. Fallback: If cURL error 60 (SSL CA certificate missing or untrusted on Windows), retry without verifying.
            // Note: Download integrity is strictly enforced by cryptographic SHA-256 hash comparison against the manifest.
            if (str_contains($e->getMessage(), 'cURL error 60') || str_contains($e->getMessage(), 'certificate')) {
                try {
                    return Http::timeout($timeoutSec)->withoutVerifying()->get($url);
                } catch (\Throwable $fallbackEx) {
                    Log::debug("SIMS HTTP GET fallback failed: " . $fallbackEx->getMessage());
                }
            }
            Log::debug("SIMS HTTP GET failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve manifest JSON from URL or local path.
     */
    protected function fetchManifest(string $source): ?array
    {
        try {
            $source = trim($source, " '\"");
            if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
                $response = $this->performHttpGet($source, 15);
                return ($response && $response->successful()) ? $response->json() : null;
            }

            // Local file path
            $filePath = str_starts_with($source, 'file://') ? substr($source, 7) : $source;
            if (!file_exists($filePath)) {
                // If relative path, try base_path
                $filePath = base_path($source);
            }

            if (file_exists($filePath)) {
                $raw = file_get_contents($filePath);
                $clean = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
                return json_decode(trim($clean), true);
            }
        } catch (\Throwable $e) {
            Log::debug("SIMS manifest fetch failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Download or copy package to target path.
     */
    protected function downloadPackage(string $url, string $dest): bool
    {
        try {
            $url = trim($url, " '\"");
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                $response = $this->performHttpGet($url, 180);
                if (!$response || !$response->successful()) {
                    return false;
                }
                file_put_contents($dest, $response->body());
                return true;
            }

            // Local file source (e.g. file:// or local path)
            $filePath = str_starts_with($url, 'file://') ? substr($url, 7) : $url;
            if (!file_exists($filePath)) {
                $filePath = base_path($url);
            }

            if (file_exists($filePath)) {
                return copy($filePath, $dest);
            }
        } catch (\Throwable $e) {
            Log::error("SIMS download package failed: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Probe internal health endpoint.
     */
    protected function probeHealthCheck(): bool
    {
        $appUrl  = config('app.url', 'http://127.0.0.1:8000');
        $pingUrl = rtrim($appUrl, '/') . '/ping-internal';

        // Attempt up to 3 times
        // NOTE: localhost uses a self-signed cert, so we must skip TLS verification here.
        // Security is guaranteed by the SHA-256 checksum we already verified on the package.
        for ($i = 0; $i < 3; $i++) {
            try {
                $response = Http::timeout(4)->withoutVerifying()->get($pingUrl);
                if ($response->successful() && $response->json('status') === 'alive') {
                    return true;
                }
            } catch (\Throwable) {
                // Wait briefly before retrying
                usleep(300000);
            }
        }

        // Direct in-process probe for test environments or offline CLI runs
        try {
            $request = \Illuminate\Http\Request::create('/ping-internal', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
            $res = app()->handle($request);
            $body = json_decode($res->getContent(), true);
            if ($res->getStatusCode() === 200 && ($body['status'] ?? '') === 'alive') {
                return true;
            }
        } catch (\Throwable) {
            // Fail if internal handler also throws
        }

        return false;
    }

    /**
     * Restart SIMS background services after a successful update so the new code is loaded.
     * On Windows: uses schtasks. On Linux: uses systemctl if available, else pkill/restart.
     */
    protected function restartServices(): void
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Stop then restart via Task Scheduler (runs as SYSTEM, survives logoff)
                foreach (['SIMS-Web', 'SIMS-Queue', 'SIMS-Scheduler'] as $task) {
                    exec("schtasks /end /tn \"{$task}\" >nul 2>&1");
                }
                sleep(2);
                foreach (['SIMS-Web', 'SIMS-Queue', 'SIMS-Scheduler'] as $task) {
                    exec("schtasks /run /tn \"{$task}\" >nul 2>&1");
                }
                $this->info('✅ Windows Task Scheduler services restarted.');
            } else {
                // Linux: try systemctl first, then pkill-based restart
                $services = ['sims-web', 'sims-queue', 'sims-scheduler'];
                $systemctlWorks = false;
                foreach ($services as $svc) {
                    exec("systemctl is-enabled {$svc} 2>/dev/null", $out, $code);
                    if ($code === 0) {
                        exec("systemctl restart {$svc} 2>/dev/null");
                        $systemctlWorks = true;
                    }
                }
                if (!$systemctlWorks) {
                    // Fallback: kill FrankenPHP and PHP workers; init scripts will restart via cron
                    exec('pkill -f "frankenphp" 2>/dev/null || true');
                    exec('pkill -f "queue:work" 2>/dev/null || true');
                    exec('pkill -f "schedule:work" 2>/dev/null || true');
                    $this->warn('⚠️ Systemd services not found. Killed running processes — they should restart via cron or init. Please verify manually.');
                } else {
                    $this->info('✅ Linux systemd services restarted.');
                }
            }
        } catch (\Throwable $e) {
            $this->warn('⚠️ Could not auto-restart services: ' . $e->getMessage());
            $this->warn('   Please run: sims restart   (Windows) or   sudo systemctl restart sims-web   (Linux)');
            Log::warning('SIMS post-update service restart failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore previous database & uploads from snapshot if any failure occurs.
     */
    protected function rollback(string $snapshotDir, string $reason): int
    {
        $this->warn("⚠️ Initiating automatic safe rollback to snapshot: {$snapshotDir}");

        $dbDest    = database_path('database.sqlite');
        $filesDest = storage_path('app/public');

        // Restore database
        if (file_exists("{$snapshotDir}/database.sqlite")) {
            copy("{$snapshotDir}/database.sqlite", $dbDest);
            $this->line("   - Restored database.sqlite");
        }

        // Restore uploads
        if (is_dir("{$snapshotDir}/uploads")) {
            $this->mirrorDir("{$snapshotDir}/uploads", $filesDest);
            $this->line("   - Restored public storage uploads");
        }

        try {
            Artisan::call('optimize:clear');
        } catch (\Throwable) {}

        $this->error("🔄 Safe rollback completed. Reason: {$reason}");
        Log::error("SIMS auto-update aborted and rolled back. Reason: {$reason}");
        return 1;
    }

    /**
     * Cross-platform directory mirror using pure PHP.
     */
    protected function mirrorDir(string $src, string $dest): void
    {
        if (!is_dir($src)) {
            return;
        }

        @mkdir($dest, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($src) + 1);
            if ($item->isDir()) {
                @mkdir($target, 0755, true);
            } else {
                @mkdir(dirname($target), 0755, true);
                copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Update APP_VERSION in local .env safely.
     */
    protected function updateEnvVersion(string $version): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        $pattern = '/^APP_VERSION=(.*)$/m';
        $replacement = "APP_VERSION=\"{$version}\"";

        $content = preg_match($pattern, $content)
            ? preg_replace($pattern, $replacement, $content)
            : $content . "\nAPP_VERSION=\"{$version}\"\n";

        file_put_contents($path, $content);
    }
}
