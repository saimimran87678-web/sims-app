<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class SessionLockScreenController extends Controller
{
    /**
     * Display the lock screen view.
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Ensure session is marked locked on server
        if (!session('session_locked')) {
            session([
                'session_locked' => true,
                'locked_at' => now()->timestamp,
            ]);
        }

        return view('auth.lock-screen', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Explicitly lock the session on demand or on client-side idle timeout beacon.
     */
    public function lock(Request $request): JsonResponse|RedirectResponse
    {
        if (Auth::check()) {
            session([
                'session_locked' => true,
                'locked_at' => now()->timestamp,
            ]);
        }

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Livewire')) {
            return response()->json([
                'locked' => true,
                'redirect' => route('session.lock_screen'),
            ]);
        }

        return redirect()->route('session.lock_screen');
    }
}
