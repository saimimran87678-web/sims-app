<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseStatus;
use Symfony\Component\HttpFoundation\Response;

class VerifyLicense
{
    /**
     * Paths that are fully exempt from license checking.
     * These are checked FIRST before any DB access to maximise performance.
     */
    private const EXEMPT_PATHS = [
        'login',
        'logout',
        'register',
        'license-blocked',
        'license/sync',
        '_debugbar',
        'up', // Laravel health check
    ];

    /**
     * Path prefixes that are exempt (e.g. Livewire, API calls).
     */
    private const EXEMPT_PREFIXES = [
        'livewire/',
        'api/',
        'sanctum/',
        '_ignition/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // ── 1. Check exempt paths BEFORE any DB/cache access ────────────────
        if ($this->isExempt($path)) {
            // If the user manually visits /license-blocked while the license
            // is actually valid, send them to the dashboard.
            if ($path === 'license-blocked') {
                $status = LicenseStatus::getStatus();
                if ($status['stage'] !== LicenseStatus::STAGE_BLOCKED) {
                    return redirect()->route('dashboard');
                }
            }
            return $next($request);
        }

        // ── 2. Get status from cache (fast, single read) ──────────────────
        $status = LicenseStatus::getStatus();

        // ── 3. If currently blocked, do a fresh DB check on every request
        //       so the system self-heals the moment a license is activated. ─
        if ($status['stage'] === LicenseStatus::STAGE_BLOCKED) {
            $status = LicenseStatus::getStatus(true);
        }

        // ── 4. Share status with all Blade views ──────────────────────────
        view()->share('licenseStatus', $status);

        // ── 5. Block access if license is invalid ─────────────────────────
        if ($status['stage'] === LicenseStatus::STAGE_BLOCKED) {
            return redirect()->route('license.blocked');
        }

        return $next($request);
    }

    private function isExempt(string $path): bool
    {
        foreach (self::EXEMPT_PATHS as $exempt) {
            if ($path === $exempt || str_starts_with($path, $exempt . '/')) {
                return true;
            }
        }

        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     * This ensures the auto-sync happens completely in the background
     * without slowing down the user's page load time.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Don't trigger auto-sync on exempt paths (like assets, livewire polls)
        if ($this->isExempt($request->path()) || $request->path() === 'license-blocked') {
            return;
        }

        $record = LicenseStatus::getLicenseRecord();
        if ($record && $record->last_online_verified_at) {
            $lastVerified = \Carbon\Carbon::parse($record->last_online_verified_at);
            $now = \Carbon\Carbon::now();

            // Sync if more than 60 mins passed OR if it's a new day
            if ($now->diffInMinutes($lastVerified) >= 60 || !$lastVerified->isSameDay($now)) {
                \App\Services\LicenseSyncService::syncBackground();
            }
        }
    }
}
