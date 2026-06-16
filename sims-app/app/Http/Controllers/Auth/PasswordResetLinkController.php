<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request (Generate & Send OTP).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'This email address is not registered in our system.',
        ]);

        $email = $request->email;
        $user = User::where('email', $email)->first();

        if ($user && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => __('Your account has been disabled.'),
            ]);
        }

        $otp = rand(100000, 999999);

        // Save OTP to password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $otp,
                'created_at' => now(),
            ]
        );

        // Send OTP via Email
        try {
            $instituteName = \App\Models\Setting::get('institute_name', 'IMCB G-6/2');
            Mail::send([], [], function ($message) use ($email, $otp, $instituteName) {
                $message->to($email)
                    ->subject('Reset code for "' . $instituteName . '"')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;'>
                            <div style='text-align: center; margin-bottom: 24px;'>
                                <h2 style='color: #1e3a5f; margin: 0; font-size: 24px; font-weight: 800;'>{$instituteName}</h2>
                                <p style='color: #64748b; margin: 4px 0 0 0; font-size: 13px;'>Powered by Adminova</p>
                            </div>
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 24px;'>
                            <p style='font-size: 15px; color: #334155; line-height: 1.5;'>Hello,</p>
                            <p style='font-size: 15px; color: #334155; line-height: 1.5;'>We received a request to reset your password. Use the following 6-digit One Time Password (OTP) code to proceed with the reset process:</p>
                            <div style='text-align: center; margin: 36px 0;'>
                                <span style='font-size: 36px; font-weight: 800; letter-spacing: 6px; color: #1e3a5f; padding: 12px 24px; background-color: #f1f5f9; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-block;'>{$otp}</span>
                            </div>
                            <p style='color: #ef4444; font-size: 13px; line-height: 1.5; margin-bottom: 0;'><strong>Note:</strong> This OTP code is valid for 15 minutes. If you did not request a password reset, please ignore this email.</p>
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-top: 24px; margin-bottom: 24px;'>
                            <p style='font-size: 11px; color: #94a3b8; text-align: center; margin: 0;'>© " . date('Y') . " Adminova. All rights reserved.</p>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Failed to send OTP email: ' . $e->getMessage()]);
        }

        // Store email in session to allow OTP verification
        session(['reset_email' => $email]);

        return redirect()->route('password.otp.verify')->with('status', 'OTP code has been sent to your email address.');
    }

    /**
     * Display the OTP verification view.
     */
    public function showVerifyOtpForm(): View|RedirectResponse
    {
        if (!session()->has('reset_email')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Please enter your email first.']);
        }

        return view('auth.verify-otp');
    }

    /**
     * Handle the OTP verification code validation.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        if (!session()->has('reset_email')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Session expired. Please enter email again.']);
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $email = session('reset_email');
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || $record->token !== $request->otp) {
            return back()->withErrors(['otp' => 'The entered OTP code is incorrect.']);
        }

        // Check expiration (15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return back()->withErrors(['otp' => 'The OTP code has expired. Please request a new one.']);
        }

        // Mark session as verified
        session(['otp_verified' => true]);

        return redirect()->route('password.reset')->with('status', 'OTP verified successfully. Please enter your new password.');
    }
}
