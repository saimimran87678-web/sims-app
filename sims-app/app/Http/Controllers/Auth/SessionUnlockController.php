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
     * Instantly unlock an expired session with credentials.
     * High performance, zero-redirect JSON response.
     */
    public function unlock(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided password does not match our records.'
            ], 422);
        }

        // Re-authenticate user and regenerate session
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'token' => csrf_token(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }
}
