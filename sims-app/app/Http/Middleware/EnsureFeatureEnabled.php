<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenseStatus;

class EnsureFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $module The module key to verify (e.g. fees, exams, attendance, whatsapp, reports)
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!LicenseStatus::isModuleEnabled($module)) {
            $formattedName = ucfirst($module);
            if ($module === 'whatsapp') $formattedName = 'WhatsApp Integration';
            if ($module === 'fees') $formattedName = 'Fee Management';
            if ($module === 'students') $formattedName = 'Student Management';
            if ($module === 'gradebook') $formattedName = 'Gradebook';

            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json([
                    'success' => false,
                    'message' => "The {$formattedName} module is currently disabled by administrative license policy.",
                ], 403);
            }

            $user = auth()->user();
            $redirectRoute = ($user && $user->isTeacher() && !$user->isAdmin())
                ? 'teacher.dashboard'
                : 'admin.dashboard';

            return redirect()->route($redirectRoute)
                ->with('error', "The {$formattedName} module is currently disabled by administrative license policy.");
        }

        return $next($request);
    }
}
