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
        // Enforce session/shift scoped permissions for shared admin features
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            // Super Admins bypass session scoping (global access)
            if ($user->hasRole('Super Admin')) {
                return null;
            }

            // Check if the permission is a Spatie permission
            $allSpatiePermissions = cache()->remember('spatie_permission_names', 3600, function () {
                return \Spatie\Permission\Models\Permission::pluck('name')->toArray();
            });

            if (in_array($ability, $allSpatiePermissions)) {
                static $requestPermissionCache = [];

                $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
                if (!$activeSessionId) {
                    return false;
                }

                $shiftType = session('selected_shift_type', 'morning');
                $cacheKey = "{$user->id}_{$activeSessionId}_{$shiftType}";

                if (!isset($requestPermissionCache[$cacheKey])) {
                    $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
                    $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
                    $effectiveShift = $isRegular ? 'regular' : $shiftType;

                    $query = \Illuminate\Support\Facades\DB::table('session_user_permissions')
                        ->where('user_id', $user->id)
                        ->where('academic_session_id', $activeSessionId);

                    if ($effectiveShift === 'both') {
                        $requestPermissionCache[$cacheKey] = $query->whereIn('shift_type', ['morning', 'evening', 'both'])
                            ->pluck('permission_name')
                            ->flip()
                            ->toArray();
                    } else {
                        $requestPermissionCache[$cacheKey] = $query->where('shift_type', $effectiveShift)
                            ->pluck('permission_name')
                            ->flip()
                            ->toArray();
                    }
                }

                return isset($requestPermissionCache[$cacheKey][$ability]);
            }

            return null;
        });

        // Enforce global read-only database writes restriction when license is locked or expired
        // We use beforeExecuting to intercept and block the query BEFORE it hits the database.
        \Illuminate\Support\Facades\DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) {
            // Fast exit: Exempt console commands unless test configuration is set
            if (app()->runningInConsole() && !config('services.license.test_write_block', false)) {
                return;
            }

            // Fast exit: Read queries (SELECT, PRAGMA, EXPLAIN) bypass license check immediately
            $firstWord = strtoupper(substr(ltrim($sql), 0, 6));
            if (in_array($firstWord, ['SELECT', 'EXPLAI', 'PRAGMA', 'SHOW  '])) {
                return;
            }

            $sqlLower = trim(strtolower($sql));
            
            // Check if query is a state-modifying statement
            $isWrite = str_starts_with($sqlLower, 'insert') || 
                       str_starts_with($sqlLower, 'update') || 
                       str_starts_with($sqlLower, 'delete') || 
                       str_starts_with($sqlLower, 'replace');

            if ($isWrite) {
                // Allow write operations to session, cache, and licensing tables
                $isExempt = str_contains($sqlLower, 'software_licenses') || 
                            str_contains($sqlLower, 'sessions') ||
                            str_contains($sqlLower, 'cache') ||
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
