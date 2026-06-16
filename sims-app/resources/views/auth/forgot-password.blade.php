<x-guest-layout>
    <div class="glass-card p-8">
        {{-- School Logo & Title --}}
        <div class="text-center mb-8">
            <div class="school-logo">
                <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                </svg>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e3a5f; margin: 0;">Forgot Password</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Reset password for {{ \App\Models\Setting::get('institute_name', 'IMCB G-6/2') }}</p>
        </div>

        <div style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 24px; text-align: center;">
            {{ __('Forgot your password? No problem. Enter your email and we will send you a 6-digit verification code (OTP) to reset your password.') }}
        </div>

        {{-- Session Status --}}
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            {{-- Email Address --}}
            <div style="margin-bottom: 24px;">
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Email Address
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </span>
                    <input id="email" class="input-modern" style="padding-left: 44px;" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            {{-- Send Code Button --}}
            <button type="submit" class="btn-login">
                <span style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    Send Reset Code
                </span>
            </button>
        </form>

        {{-- Return to Login Link --}}
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <a class="link-modern" style="font-size: 13px; display: inline-flex; align-items: center; gap: 4px;" href="{{ route('login') }}">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                Back to sign in
            </a>
        </div>
    </div>

    {{-- Footer --}}
    <div style="text-align: center; margin-top: 32px; display: flex; flex-direction: column; gap: 8px;">
        <p style="font-size: 12px; color: rgba(255, 255, 255, 0.7); font-weight: 500; margin: 0;">
            Powered by <strong style="color: #ffffff;">Adminova</strong> Information Management System
        </p>
        <p style="font-size: 11px; color: rgba(255, 255, 255, 0.5); margin: 0;">
            © {{ date('Y') }} All Rights Reserved.
        </p>
    </div>
</x-guest-layout>
