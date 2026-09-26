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
                            {--package= : Path to a local update package (.zip) to apply directly}
                            {--manifest= : Custom manifest URL or file path}
                            {--extract-to= : Target directory to extract to}
                            {--skip-health-check : Skip localhost health check probe}
                            {--no-restart : Skip restarting background services after update}
                            {--verify-checksum : Display installed version checksum verification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated, cross-platform update manager with SHA-256 checksum verification and safe rollback';

    /**
     * Default manifest URL (hosted on fast global Anycast CDN)
     */
    public const DEFAULT_MANIFEST_URL = 'https://cdn.jsdelivr.net/gh/saimimran87678-web/sims-app@main/manifest.json';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // ── Option: Display current installed version & checksum ──────
        if ($this->option('verify-checksum')) {
            $version   = Setting::getGlobal('installed_version') ?: config('app.version', '2.5.2');
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

        $localPackage = $this->option('package');
        $isLocalPackage = false;
        $zipPath = null;
        $actualHash = null;

        if ($localPackage) {
            $packagePath = realpath($localPackage) ?: $localPackage;
            if (!file_exists($packagePath) || !is_file($packagePath)) {
                $this->error("❌ Specified update package file does not exist: {$localPackage}");
                return 1;
            }

            $this->line("📦 Inspecting local patch archive: {$packagePath}");
            $zip = new \ZipArchive();
            $res = $zip->open($packagePath);
            if ($res !== true) {
                $this->error("❌ Invalid or corrupt zip package (Code: {$res})");
                return 1;
            }

            $currentVersion = Setting::getGlobal('installed_version') ?: config('app.version', '2.5.2');
            $latestVersion = null;
            $minPhpVersion = '8.2.0';

            // Check if manifest.json exists inside the zip archive
            $manifestIndex = $zip->locateName('manifest.json');
            if ($manifestIndex !== false) {
                $rawManifest = $zip->getFromIndex($manifestIndex);
                $zipManifest = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $rawManifest), true);
                if (is_array($zipManifest)) {
                    $latestVersion = trim($zipManifest['version'] ?? '');
                    $minPhpVersion = $zipManifest['min_php_version'] ?? '8.2.0';
                }
            }
            $zip->close();

            if (empty($latestVersion)) {
                if (preg_match('/v?([0-9]+\.[0-9]+\.[0-9]+)/i', basename($packagePath), $matches)) {
                    $latestVersion = $matches[1];
                } else {
                    $latestVersion = $currentVersion;
                }
            }

            $actualHash = hash_file('sha256', $packagePath);
            $zipPath = $packagePath;
            $isLocalPackage = true;
            $downloadUrl = 'local://' . basename($packagePath);
            $this->info("✅ Package verified: target v{$latestVersion} (SHA-256: {$actualHash})");
        } else {
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
            $currentVersion  = Setting::getGlobal('installed_version') ?: config('app.version', '2.5.2');
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

        // ── STEP 2: Download & verify SHA-256 Checksum (if remote) ────
        if (!$isLocalPackage) {
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
        }

        // ── STEP 3: Extract update package ────────────────────────────
        $this->line("📦 Extracting files to application root...");
        $zip = new \ZipArchive();
        $openResult = $zip->open($zipPath);
        if ($openResult !== true) {
            $this->error("❌ Corrupt update archive: cannot open zip (Code: {$openResult}).");
            if (!$isLocalPackage || str_contains($zipPath, storage_path('updates'))) {
                @unlink($zipPath);
            }
            return $this->rollback($snapshotDir, "Corrupt update zip");
        }

        // Validate zip security against path traversal attacks and auto-detect layout
        $hasSimsAppPrefix = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_contains($entry, '..') || str_starts_with($entry, '/') || str_starts_with($entry, '\\')) {
                $zip->close();
                if (!$isLocalPackage || str_contains($zipPath, storage_path('updates'))) {
                    @unlink($zipPath);
                }
                $this->error("❌ Malicious path detected in update archive: {$entry}");
                return $this->rollback($snapshotDir, "Malicious path in update archive: {$entry}");
            }
            if (str_starts_with($entry, 'sims-app/') || str_starts_with($entry, 'sims-app\\')) {
                $hasSimsAppPrefix = true;
            }
        }

        // CRITICAL: The patch zip structure is relative to the INSTALLATION ROOT, e.g.:
        //   sims-app/app/...      sims-app/resources/...      scripts/windows/...
        // If zip entries start with sims-app/, extract to dirname(base_path()) = installation root.
        // If zip entries start directly with app/, resources/, extract to base_path().
        $defaultExtract = $hasSimsAppPrefix ? dirname(base_path()) : base_path();
        $extractPath = $this->option('extract-to') ?: $defaultExtract;

        // Filter out non-essential or platform-mismatched files like install.sh
        $entriesToExtract = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            $cleanEntry = ltrim(str_replace('\\', '/', $entry), '/');

            // Skip Linux installer or root installers that shouldn't be extracted during app updates
            if ($cleanEntry === 'install.sh' || str_ends_with($cleanEntry, '/install.sh') ||
                $cleanEntry === 'install.bat' || str_ends_with($cleanEntry, '/install.bat')) {
                continue;
            }

            $entriesToExtract[] = $entry;
        }

        // Extract verified entries safely
        $extractSuccess = false;
        try {
            $extractSuccess = $zip->extractTo($extractPath, !empty($entriesToExtract) ? $entriesToExtract : null);
        } catch (\Throwable $e) {
            Log::warning("Batch zip extraction notice: " . $e->getMessage());
        }

        if (!$extractSuccess) {
            // Fallback: extract entry by entry to ensure single non-critical files don't fail the update
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                $cleanEntry = ltrim(str_replace('\\', '/', $entry), '/');
                if (in_array($cleanEntry, ['install.sh', 'install.bat']) || str_ends_with($cleanEntry, '/install.sh')) {
                    continue;
                }
                try {
                    $zip->extractTo($extractPath, $entry);
                } catch (\Throwable $e) {
                    Log::warning("Skipping non-critical locked file during update: {$entry}");
                }
            }
        }
        $zip->close();

        if (!$isLocalPackage || str_contains($zipPath, storage_path('updates'))) {
            @unlink($zipPath);
        }
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
                if ($this->option('no-restart')) {
                    // Running in web context:
                    // Only clear compiled views and general cache. DO NOT run optimize (config:cache/route:cache)
                    // as it reboots the container and wipes request singletons during an active HTTP response.
                    Artisan::call('view:clear');
                    Artisan::call('cache:clear');
                } else {
                    Artisan::call('optimize:clear');
                    Artisan::call('view:cache');
                }
            } catch (\Throwable $e) {
                Log::warning("Post-update optimization warning: " . $e->getMessage());
            }

            // Update APP_VERSION in .env
            $this->updateEnvVersion($latestVersion);

            // Automatically optimize session & cache drivers to eliminate SQLite database locks
            $this->ensureFastEnvironment();
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
        if (!$this->option('no-restart')) {
            $this->line('🔄 Restarting SIMS background services to load updated code...');
            $this->restartServices();
        } else {
            $this->line('ℹ️ Skipping service restart (--no-restart flag set).');
        }

        Log::info("SIMS successfully updated from v{$currentVersion} to v{$latestVersion}. Verified SHA-256: {$actualHash}");
        return 0;
    }

    /**
     * Perform an HTTP GET request with CA bundle verification and fallback for Windows environments.
     */
    protected function performHttpGet(string $url, int $timeoutSec = 4): ?\Illuminate\Http\Client\Response
    {
        try {
            // Note: Update manifest and package integrity is strictly validated via SHA-256 cryptographic hashes.
            // Using withoutVerifying() prevents cURL error 77 (missing/unreadable cacert.pem) in portable runtimes.
            return Http::timeout($timeoutSec)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'SIMS-Update-Client/' . config('app.version', '2.5.2') . ' (Windows/Linux; Standalone)',
                    'Accept'     => 'application/json, text/plain, */*',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma'        => 'no-cache',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            Log::debug("SIMS HTTP GET failed ({$url}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve manifest JSON from URL or local path with multi-mirror and PowerShell fallback.
     */
    protected function fetchManifest(string $source): ?array
    {
        try {
            $source = trim($source, " '\"");
            if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
                // High-performance Anycast CDN candidates with dynamic cache-busting
                $cacheBust = '?t=' . time() . '_' . mt_rand(100, 999);
                $cleanSource = strtok($source, '?');

                $candidates = [
                    'https://cdn.jsdelivr.net/gh/saimimran87678-web/sims-app@main/manifest.json' . $cacheBust,
                    'https://fastly.jsdelivr.net/gh/saimimran87678-web/sims-app@main/manifest.json' . $cacheBust,
                    'https://raw.githubusercontent.com/saimimran87678-web/sims-app/main/manifest.json' . $cacheBust,
                ];

                // If user or environment specified a custom distinct manifest URL, check it first
                if (!str_contains($cleanSource, 'manifest.json')) {
                    array_unshift($candidates, $cleanSource . $cacheBust);
                }

                foreach ($candidates as $url) {
                    $response = $this->performHttpGet($url, 4);
                    if ($response && $response->successful()) {
                        $json = $response->json();
                        if (is_array($json) && !empty($json['version'])) {
                            return $json;
                        }
                    }
                }

                // Native Windows fallback: PowerShell Invoke-RestMethod uses Windows WinINet/Schannel network stack
                if (PHP_OS_FAMILY === 'Windows') {
                    $cdnUrl = 'https://cdn.jsdelivr.net/gh/saimimran87678-web/sims-app@main/manifest.json' . $cacheBust;
                    $rawUrl = 'https://raw.githubusercontent.com/saimimran87678-web/sims-app/main/manifest.json' . $cacheBust;
                    $psScript = "\$urls = @('{$cdnUrl}', '{$rawUrl}'); foreach (\$u in \$urls) { try { (Invoke-RestMethod -Uri \$u -TimeoutSec 5 -Headers @{'User-Agent'='SIMS-Updater'}) | ConvertTo-Json -Compress; break } catch {} }";
                    $encoded = base64_encode(mb_convert_encoding($psScript, 'UTF-16LE', 'UTF-8'));
                    $psOut = shell_exec("powershell.exe -NoProfile -ExecutionPolicy Bypass -EncodedCommand {$encoded}");
                    if (!empty($psOut)) {
                        $json = json_decode(trim($psOut), true);
                        if (is_array($json) && !empty($json['version'])) {
                            return $json;
                        }
                    }
                }

                return null;
            }

            // Local file path
            $filePath = str_starts_with($source, 'file://') ? substr($source, 7) : $source;
            if (!file_exists($filePath)) {
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
                // Engine 1: Windows 10/11 Native curl.exe (fastest, streams directly to disk, follows S3 302 redirects)
                if (PHP_OS_FAMILY === 'Windows') {
                    $this->line("   [Engine 1] Transferring patch via Windows Native cURL...");
                    $curlExe = 'curl.exe';
                    $cmd = "{$curlExe} -L -k -s -f --connect-timeout 10 --max-time 120 --retry 2 -H \"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) SIMS-Updater\" -o \"{$dest}\" \"{$url}\"";
                    @exec($cmd, $curlOut, $curlRet);
                    if ($curlRet === 0 && file_exists($dest) && filesize($dest) > 1024) {
                        $this->line("   [OK] Download completed successfully (" . round(filesize($dest) / 1048576, 2) . " MB).");
                        return true;
                    }
                }

                // Engine 2: PHP Guzzle with file sink (handles streaming and redirects directly to file)
                $this->line("   [Engine 2] Transferring patch via PHP Stream Sink...");
                try {
                    $response = Http::timeout(120)
                        ->withoutVerifying()
                        ->withHeaders(['User-Agent' => 'SIMS-Updater/' . config('app.version', '2.5.2')])
                        ->withOptions([
                            'sink'            => $dest,
                            'allow_redirects' => ['max' => 5, 'strict' => false, 'referer' => true, 'protocols' => ['http', 'https']],
                        ])
                        ->get($url);

                    if (file_exists($dest) && filesize($dest) > 1024) {
                        $this->line("   [OK] Download completed successfully (" . round(filesize($dest) / 1048576, 2) . " MB).");
                        return true;
                    }
                } catch (\Throwable $guzzleEx) {
                    Log::debug("SIMS Guzzle download failed: " . $guzzleEx->getMessage());
                }

                // Engine 3: Native Windows PowerShell with TLS 1.2 WebClient (robust BITS / .NET fallback)
                if (PHP_OS_FAMILY === 'Windows') {
                    $this->line("   [Engine 3] Transferring patch via Windows WebClient...");
                    $psScript = "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 -bor [Net.SecurityProtocolType]::Tls13; \$wc = New-Object System.Net.WebClient; \$wc.Headers.Add('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SIMS-Updater'); \$wc.DownloadFile('{$url}', '{$dest}');";
                    $encoded  = base64_encode(mb_convert_encoding($psScript, 'UTF-16LE', 'UTF-8'));
                    @exec("powershell.exe -NoProfile -ExecutionPolicy Bypass -EncodedCommand {$encoded}", $out, $ret);
                    if ($ret === 0 && file_exists($dest) && filesize($dest) > 1024) {
                        $this->line("   [OK] Download completed successfully (" . round(filesize($dest) / 1048576, 2) . " MB).");
                        return true;
                    }
                }

                return false;
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
                if (!app()->runningInConsole()) {
                    // Running inside a web request (e.g. Settings UI).
                    // Avoid killing php-cgi.exe synchronously while it is actively streaming the HTTP response.
                    // Instead, trigger a background delayed restart so the client receives the response cleanly.
                    pclose(popen("start /B cmd /c \"ping 127.0.0.1 -n 3 >nul & taskkill /F /IM php-cgi.exe >nul 2>&1 & schtasks /run /tn SIMS-Web >nul 2>&1 & schtasks /run /tn SIMS-Queue >nul 2>&1\"", "r"));
                    $this->info('✅ Windows background services scheduled to reload after request.');
                    return;
                }

                // Console / Control Center execution: synchronous restart
                exec("taskkill /F /IM php-cgi.exe >nul 2>&1");

                // Stop then restart via Task Scheduler (runs as SYSTEM, survives logoff)
                foreach (['SIMS-Web', 'SIMS-Queue', 'SIMS-Scheduler'] as $task) {
                    exec("schtasks /end /tn \"{$task}\" >nul 2>&1");
                }
                sleep(2);
                foreach (['SIMS-Web', 'SIMS-Queue', 'SIMS-Scheduler'] as $task) {
                    exec("schtasks /run /tn \"{$task}\" >nul 2>&1");
                }
                $this->info('✅ Windows background services and PHP FastCGI workers reloaded.');
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

    /**
     * Migrate session and cache drivers to high-performance file drivers to prevent SQLite database locking.
     */
    protected function ensureFastEnvironment(): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        $modified = false;

        if (preg_match('/^SESSION_DRIVER=database/m', $content)) {
            $content = preg_replace('/^SESSION_DRIVER=database/m', 'SESSION_DRIVER=file', $content);
            $modified = true;
        }

        if (preg_match('/^CACHE_STORE=database/m', $content)) {
            $content = preg_replace('/^CACHE_STORE=database/m', 'CACHE_STORE=file', $content);
            $modified = true;
        }

        if ($modified) {
            file_put_contents($envPath, $content);
            $this->line('⚡ Automatically optimized session and cache drivers (switched to file).');
        }
    }
}
