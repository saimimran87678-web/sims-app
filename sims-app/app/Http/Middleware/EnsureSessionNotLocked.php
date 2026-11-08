<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionNotLocked
{
    /**
     * Paths that are exempt from the session lock check.
     */
    private const EXEMPT_PATHS = [
        'lock-screen',
        'session/lock',
        'session/unlock',
        'refresh-csrf',
        'logout',
        'login',
        'setup',
        'ping-internal',
        'license-blocked',
        'domain-blocked',
        'license/sync',
        'cert',
        '_debugbar',
        'up',
    ];

    /**
     * Path prefixes exempt from session lock check.
     */
    private const EXEMPT_PREFIXES = [
        'sanctum/',
        '_ignition/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Only applies to authenticated web users
        if (!Auth::check()) {
            return $next($request);
        }

        $path = $request->path();

        if ($this->isExempt($path)) {
            return $next($request);
        }

        $timeoutMinutes = Setting::getSessionIdleTimeout();

        // Check if session has exceeded idle timeout
        if ($timeoutMinutes > 0) {
            $lastActivity = session('last_activity_time');
            if ($lastActivity && (now()->timestamp - $lastActivity) > ($timeoutMinutes * 60)) {
                session([
                    'session_locked' => true,
                    'locked_at' => now()->timestamp,
                ]);
            }
        }

        // If session is locked, block access
        if (session('session_locked') === true) {
            // If AJAX / JSON / Livewire request
            if ($request->expectsJson() || $request->ajax() || $request->header('X-Livewire')) {
                return response()->json([
                    'locked' => true,
                    'message' => 'Your session is locked due to inactivity. Please enter your password to continue.',
                    'redirect' => route('session.lock_screen'),
                ], 423); // 423 Locked HTTP status
            }

            // Normal browser GET/POST request: remember intended URL and redirect to lock screen
            if ($request->isMethod('GET') && !$request->routeIs('session.lock_screen')) {
                session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('session.lock_screen');
        }

        // Session is active and not locked: update last activity timestamp
        session(['last_activity_time' => now()->timestamp]);

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
}
