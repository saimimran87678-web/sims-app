<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SimsInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sims:install {--force : Force regeneration of keys and cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform first-time SIMS installation: generate unique APP_KEY, prepare database, and warm caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('       🚀 SIMS Client First-Time Installation       ');
        $this->info('====================================================');

        // 0. Self-healing: Purge any stale bootstrap cache files that freeze paths
        $bootstrapCacheDir = base_path('bootstrap/cache');
        if (File::isDirectory($bootstrapCacheDir)) {
            foreach (File::glob($bootstrapCacheDir . '/*.php') as $cacheFile) {
                @unlink($cacheFile);
            }
        }

        // 1. Ensure .env exists
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (!File::exists($envPath)) {
            $this->warn('⚠️ .env not found. Creating from .env.example...');
            if (File::exists($envExamplePath)) {
                File::copy($envExamplePath, $envPath);
                $this->info('✅ Created .env file.');
                if (class_exists(\Dotenv\Dotenv::class)) {
                    \Dotenv\Dotenv::createImmutable(base_path())->safeLoad();
                }
            } else {
                $this->error('❌ Error: .env.example not found!');
                return 1;
            }
        }

        // 2. Generate unique cryptographic APP_KEY if missing or forced.
        //    CRITICAL: key:generate writes to .env then returns. The in-process config() cache still
        //    holds the OLD (empty) key. We must re-read .env after generation so that config:cache
        //    (called later) bakes in the real key — not the empty one that causes HTTP 500.
        $currentKey = config('app.key');
        if (empty($currentKey) || $this->option('force')) {
            $this->info('🔑 Generating unique cryptographic APP_KEY for this installation...');

            // Run key:generate in a separate sub-process so the new key is written to .env
            // without being contaminated by the current process's in-memory config state.
            $phpBin  = PHP_BINARY;
            $artisan = base_path('artisan');
            exec("\"{$phpBin}\" \"{$artisan}\" key:generate --force 2>&1", $keyOutput, $keyCode);

            if ($keyCode !== 0) {
                // Fallback to in-process if exec is disabled (some shared hosts)
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Re-read the freshly written APP_KEY from .env into the running process
            // so that any subsequent config:cache call encodes the real key.
            $envFilePath = base_path('.env');
            if (file_exists($envFilePath)) {
                $envContents = file_get_contents($envFilePath);
                if (preg_match('/^APP_KEY=(.+)$/m', $envContents, $matches)) {
                    $freshKey = trim($matches[1]);
                    // Inject fresh key into the current process config and env
                    config(['app.key' => $freshKey]);
                    putenv("APP_KEY={$freshKey}");
                    $_ENV['APP_KEY']    = $freshKey;
                    $_SERVER['APP_KEY'] = $freshKey;
                }
            }

            $this->info('✅ Generated new 256-bit AES master APP_KEY.');
        } else {
            $this->line('ℹ️ Cryptographic APP_KEY is already configured.');
        }

        // 3. Ensure SQLite database file exists and connection uses current absolute path
        $dbPath = database_path('database.sqlite');
        $dbDir = dirname($dbPath);

        if (!File::isDirectory($dbDir)) {
            File::makeDirectory($dbDir, 0775, true);
        }

        if (!File::exists($dbPath)) {
            $this->info('📁 Creating new database.sqlite file...');
            touch($dbPath);
            $this->info('✅ Created database.sqlite.');
        } else {
            $this->line('ℹ️ database.sqlite already exists.');
        }

        // Explicitly bind the SQLite connection to current filesystem path and reconnect
        config(['database.connections.sqlite.database' => $dbPath]);
        try {
            \Illuminate\Support\Facades\DB::purge('sqlite');
            \Illuminate\Support\Facades\DB::reconnect('sqlite');
        } catch (\Throwable $t) {
            // Ignore if DB connection not yet established
        }

        // Set safe file permissions on non-Windows environments
        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod($dbDir, 0775);
            @chmod($dbPath, 0664);
            @chmod(storage_path(), 0775);
            @chmod(base_path('bootstrap/cache'), 0775);
        }

        // 4. Run silent database migrations
        $this->info('📦 Running database migrations...');
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Database schema migrated successfully.');
        } catch (\Exception $e) {
            $this->error('❌ Database migration failed: ' . $e->getMessage());
            return 1;
        }

        // 4b. Seed initial installation checksum and version if manifest is present
        $manifestCandidates = [
            base_path('../manifest.json'),
            base_path('manifest.json'),
            base_path('public/build/manifest.json')
        ];
        foreach ($manifestCandidates as $candidate) {
            if (File::exists($candidate)) {
                $raw = File::get($candidate);
                $clean = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
                $manifestData = json_decode(trim($clean), true);
                if (is_array($manifestData) && !empty($manifestData['version']) && (!empty($manifestData['checksum']) || !empty($manifestData['sha256']))) {
                    $chk = strtolower(trim($manifestData['checksum'] ?? $manifestData['sha256']));
                    $ver = trim($manifestData['version']);
                    \App\Models\Setting::setGlobal('last_update_checksum', $chk);
                    \App\Models\Setting::setGlobal('installed_version', $ver);
                    \App\Models\Setting::setGlobal('last_updated_at', now()->toIso8601String());
                    $this->info("🔖 Seeded release tracking metadata: v{$ver} ({$chk})");
                    break;
                }
            }
        }

        // 5. Ensure storage symlink exists
        try {
            Artisan::call('storage:link', ['--force' => true]);
            $this->info('✅ Public storage symlink verified.');
        } catch (\Exception $e) {
            $this->warn('⚠️ Storage link note: ' . $e->getMessage());
        }

        // 6. Pre-warm and compile application caches
        $this->info('⚡ Pre-warming application caches (config, routes, views)...');
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            $this->info('✅ Configuration, route, and view caches compiled.');
        } catch (\Exception $e) {
            $this->warn('⚠️ Cache compilation note: ' . $e->getMessage());
        }

        $this->info('====================================================');
        $this->info('✨ First-time installation completed successfully!  ');
        $this->info('====================================================');

        if (PHP_OS_FAMILY === 'Windows') {
            $this->line('👉 Next step: Register Windows background services by running:');
            $this->info('   php artisan sims:setup-windows');
        } else {
            $this->line('👉 Next step: Register Linux background services by running:');
            $this->info('   sudo php artisan sims:setup-linux');
        }

        return 0;
    }
}
