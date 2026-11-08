<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SessionUnlockController extends Controller
{
    /**
     * Unlock a locked session using the user's password.
     * Supports both JSON AJAX requests (from the in-page modal) and standard form submissions (from the full lock screen).
     */
    public function unlock(Request $request)
    {
        $currentUser = Auth::user();

        // If email was not passed, resolve from the currently logged-in user
        $email = $request->input('email');
        if (empty($email) && $currentUser) {
            $email = $currentUser->email;
        }

        $password = (string) $request->input('password');

        if (empty($password)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is required to unlock your session.'
                ], 422);
            }
            return back()->withErrors(['password' => 'Password is required to unlock your session.']);
        }

        $user = $currentUser ?: ($email ? User::where('email', $email)->first() : null);

        if (!$user || !Hash::check($password, $user->password)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided password does not match our records.'
                ], 422);
            }

            return back()->withErrors(['password' => 'The provided password does not match our records.']);
        }

        // Re-authenticate user and regenerate session
        if (!Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
        }

        // Clear all lock flags and update last activity timestamp
        $request->session()->forget(['session_locked', 'is_locked', 'locked_at']);
        $request->session()->put('last_activity_time', now()->timestamp);
        $request->session()->regenerate();

        $intendedUrl = session()->pull('url.intended');
        if (empty($intendedUrl) || str_contains($intendedUrl, 'lock-screen') || str_contains($intendedUrl, 'login')) {
            $intendedUrl = $user->role === 'admin' 
                ? route('admin.dashboard', absolute: false) 
                : ($user->role === 'teacher' ? route('teacher.dashboard', absolute: false) : route('dashboard', absolute: false));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'token' => csrf_token(),
                'redirect' => $intendedUrl,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        }

        return redirect()->to($intendedUrl);
    }
}
