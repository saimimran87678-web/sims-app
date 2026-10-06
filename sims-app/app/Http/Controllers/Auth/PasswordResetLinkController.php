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

        if ($user && !$user->hasRole('Super Admin')) {
            $activeSessions = \App\Models\AcademicSession::where('is_active', true)->pluck('id');
            $hasActiveSession = \Illuminate\Support\Facades\DB::table('session_user')
                ->where('user_id', $user->id)
                ->whereIn('academic_session_id', $activeSessions)
                ->where('is_active', true)
                ->exists();

            if (!$hasActiveSession) {
                throw ValidationException::withMessages([
                    'email' => __('Your account has been disabled in all active shifts.'),
                ]);
            }
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
            $instituteFormalName = trim((string) \App\Models\Setting::getGlobal('institute_formal_name', ''));
            if (empty($instituteFormalName)) {
                $instituteFormalName = trim((string) \App\Models\Setting::getGlobal('institute_name', config('app.name', 'IMCB G-6/2, ISLAMABAD')));
            }
            $instituteShortName = trim((string) \App\Models\Setting::getGlobal('institute_short_name', ''));
            $userName = $user ? $user->name : null;

            $logoPath = \App\Models\Setting::getGlobal('institute_logo');
            $logoUrl = null;
            if (!empty($logoPath)) {
                $logoUrl = filter_var($logoPath, FILTER_VALIDATE_URL) ? $logoPath : url($logoPath);
            }

            // Ensure dynamic SMTP credentials and sender name are set
            $mailUser = \App\Models\Setting::getGlobal('mail_username');
            $mailPass = \App\Models\Setting::getGlobal('mail_password');
            if (!empty($mailUser) && !empty($mailPass)) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => 'smtp.gmail.com',
                    'mail.mailers.smtp.port' => 465,
                    'mail.mailers.smtp.encryption' => 'ssl',
                    'mail.mailers.smtp.username' => $mailUser,
                    'mail.mailers.smtp.password' => $mailPass,
                    'mail.from.address' => $mailUser,
                    'mail.from.name' => $instituteFormalName,
                ]);
            }

            Mail::to($email)->send(new \App\Mail\PasswordResetOtpMail(
                otp: $otp,
                instituteName: $instituteFormalName,
                userName: $userName,
                logoUrl: $logoUrl,
                validMinutes: 15,
                instituteShortName: $instituteShortName
            ));
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
