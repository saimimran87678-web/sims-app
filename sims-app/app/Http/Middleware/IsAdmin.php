<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized access.');
        }

        // Teachers with shared permissions should use teacher panel routes, not admin routes
        if ($user->role === 'teacher') {
            // Redirect to teacher dashboard - they should use /teacher/shared/* routes
            return redirect()->route('teacher.dashboard');
        }

        // Allow only actual admins or Super Admins
        if ($user->role === 'admin' || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}
