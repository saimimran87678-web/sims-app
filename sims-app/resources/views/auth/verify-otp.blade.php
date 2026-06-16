<x-guest-layout>
    <div class="glass-card p-8">
        {{-- School Logo & Title --}}
        <div class="text-center mb-8">
            <div class="school-logo">
                <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e3a5f; margin: 0;">Verify OTP</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Enter the 6-digit code sent to your email</p>
        </div>

        <div style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 24px; text-align: center;">
            An OTP verification code was sent to:<br>
            <strong style="color: #1e3a5f;">{{ session('reset_email') }}</strong>
        </div>

        {{-- Status Notification --}}
        @if (session('status'))
            <div style="padding: 10px; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); border-radius: 8px; color: #15803d; font-size: 13px; text-align: center; margin-bottom: 20px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.otp.submit') }}">
            @csrf

            {{-- OTP Code Input --}}
            <div style="margin-bottom: 24px;">
                <label for="otp" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; text-align: center;">
                    6-Digit OTP Code
                </label>
                <div style="position: relative; max-width: 200px; margin: 0 auto;">
                    <input id="otp" class="input-modern" style="text-align: center; letter-spacing: 6px; font-size: 20px; font-weight: 700; padding: 12px;" type="text" name="otp" pattern="\d{6}" maxlength="6" required autofocus placeholder="------" autocomplete="one-time-code">
                </div>
                <x-input-error :messages="$errors->get('otp')" class="mt-2 text-center" />
            </div>

            {{-- Verify Button --}}
            <button type="submit" class="btn-login">
                <span style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    Verify OTP Code
                </span>
            </button>
        </form>

        {{-- Back / Resend Link --}}
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <a class="link-modern" style="font-size: 13px; display: inline-flex; align-items: center; gap: 4px;" href="{{ route('password.request') }}">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                Change Email
            </a>
            
            <form method="POST" action="{{ route('password.email') }}" style="display: inline;">
                @csrf
                <input type="hidden" name="email" value="{{ session('reset_email') }}">
                <button type="submit" style="background: none; border: none; padding: 0; color: #3182ce; font-size: 13px; cursor: pointer; text-decoration: underline;" onmouseover="this.style.color='#2b6cb0'" onmouseout="this.style.color='#3182ce'">
                    Resend Code
                </button>
            </form>
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
