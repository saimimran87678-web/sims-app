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
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if (app()->runningInConsole()) {
                return;
            }

            $sql = trim(strtolower($query->sql));
            
            // Check if query is a state-modifying statement
            $isWrite = str_starts_with($sql, 'insert') || 
                       str_starts_with($sql, 'update') || 
                       str_starts_with($sql, 'delete') || 
                       str_starts_with($sql, 'replace');

            if ($isWrite) {
                // Allow write operations to session, cache, and licensing tables
                $isExempt = str_contains($sql, 'software_licenses') || 
                            str_contains($sql, 'sessions') ||
                            str_contains($sql, 'cache');

                if (!$isExempt && !\App\Services\LicenseStatus::canWrite()) {
                    throw new \Exception('Database is in READ-ONLY mode. Please renew your subscription to resume editing.');
                }
            }
        });
    }
}
