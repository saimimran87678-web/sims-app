<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce global read-only database writes restriction when license is locked or expired
        // We use beforeExecuting to intercept and block the query BEFORE it hits the database.
        \Illuminate\Support\Facades\DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) {
            // Exempt artisan commands from write-blocking. 
            // During tests, we explicitly enable it via config to verify the logic.
            if (app()->runningInConsole() && !config('services.license.test_write_block', false)) {
                return;
            }

            $sql = trim(strtolower($sql));
            
            // Check if query is a state-modifying statement
            $isWrite = str_starts_with($sql, 'insert') || 
                       str_starts_with($sql, 'update') || 
                       str_starts_with($sql, 'delete') || 
                       str_starts_with($sql, 'replace');

            if ($isWrite) {
                // Allow write operations to session, cache, and licensing tables
                // Also explicitly exempt the login and logout routes so users can log back in 
                // to see the dashboard and access the Renew button if their session expires.
                $isExempt = str_contains($sql, 'software_licenses') || 
                            str_contains($sql, 'sessions') ||
                            str_contains($sql, 'cache') ||
                            request()->is('login') || 
                            request()->is('logout') ||
                            request()->is('license/sync') ||
                            request()->is('license-blocked/activate');

                if (!$isExempt && !\App\Services\LicenseStatus::canWrite()) {
                    \Illuminate\Support\Facades\Log::warning('Blocked Query: ' . $sql);
                    throw new \App\Exceptions\LicenseLockedException('Database is in READ-ONLY mode. Please renew your subscription to resume editing.');
                }
            }
        });
    }
}
