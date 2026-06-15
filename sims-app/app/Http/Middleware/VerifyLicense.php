<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseStatus;
use Symfony\Component\HttpFoundation\Response;

class VerifyLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Compute/Fetch license status
        $status = LicenseStatus::getStatus();

        // If the system is currently blocked, or the user is on the blocked page, force a fresh DB check
        if (($status['stage'] ?? null) === LicenseStatus::STAGE_BLOCKED || $request->path() === 'license-blocked') {
            $status = LicenseStatus::getStatus(true);
        }

        // 2. Exclude checking on asset/livewire/auth routes to prevent infinite loops
        $path = $request->path();
        
        $exemptPaths = [
            'license-blocked',
            'login',
            'logout',
            'register',
            '_debugbar',
        ];

        // Exempt routes starting with these prefix patterns
        $exemptPrefixes = [
            'livewire/',
            'api/',
            'sanctum/',
        ];

        $isExempt = false;
        foreach ($exemptPaths as $exempt) {
            if ($path === $exempt || str_starts_with($path, $exempt . '/')) {
                $isExempt = true;
                break;
            }
        }

        if (!$isExempt) {
            foreach ($exemptPrefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $isExempt = true;
                    break;
                }
            }
        }

        // 3. Share status with all views globally
        view()->share('licenseStatus', $status);

        // 4. Force redirect if status is BLOCKED
        if ($status['stage'] === LicenseStatus::STAGE_BLOCKED && !$isExempt) {
            return redirect()->route('license.blocked');
        }

        // 5. If they try to visit license-blocked page while not blocked, redirect them home
        if ($path === 'license-blocked' && $status['stage'] !== LicenseStatus::STAGE_BLOCKED) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
