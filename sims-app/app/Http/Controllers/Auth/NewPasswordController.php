<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (!session()->has('reset_email') || !session()->has('otp_verified')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Please verify your OTP code first.']);
        }

        return view('auth.reset-password');
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (!session()->has('reset_email') || !session()->has('otp_verified')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Session expired. Please start over.']);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = session('reset_email');
        $user = User::where('email', $email)->firstOrFail();

        // Update user password
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        // Clean up password_reset_tokens table and sessions
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget(['reset_email', 'otp_verified']);

        return redirect()->route('login')->with('status', 'Your password has been successfully reset. You can now sign in.');
    }
}
