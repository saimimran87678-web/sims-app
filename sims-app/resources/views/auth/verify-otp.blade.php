<x-guest-layout>
    <div class="glass-card p-8">
        {{-- School Logo & Title --}}
        <div class="text-center mb-8">
            <div class="school-logo" style="{{ \App\Models\Setting::getGlobal('institute_logo') ? 'background: #ffffff;' : '' }}">
                @php
                    $logoPath = \App\Models\Setting::getGlobal('institute_logo');
                @endphp
                @if($logoPath && file_exists(public_path($logoPath)))
                    <img src="{{ '/' . $logoPath }}" style="width: 100%; height: 100%; object-fit: contain; padding: 6px; border-radius: 20px;">
                @else
                    <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                @endif
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e3a5f; margin: 0;">Verify OTP</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Enter the 6-digit code sent to your email</p>
        </div>

        <div style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 24px; text-align: center;">
            An OTP verification code was sent to:<br>
            <strong style="color: #1e3a5f;">{{ session('reset_email') }}</strong>
        </div>

        {{-- Status / Error Notifications --}}
        @if (session('status'))
            <div style="padding: 10px; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); border-radius: 8px; color: #15803d; font-size: 13px; text-align: center; margin-bottom: 20px;">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div style="padding: 10px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; color: #dc2626; font-size: 13px; text-align: center; margin-bottom: 20px;">
                {{ session('error') }}
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
            
            <form id="form-resend-otp" method="POST" action="{{ route('password.email') }}" style="display: inline;">
                @csrf
                <input type="hidden" name="email" value="{{ session('reset_email') }}">
                <button type="submit" id="btn-resend-otp" 
                        style="background: none; border: none; padding: 4px 6px; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; border-radius: 6px; transition: all 0.2s; {{ ($secondsRemaining ?? 0) > 0 ? 'color: #94a3b8; cursor: not-allowed;' : 'color: #3182ce; cursor: pointer; text-decoration: underline;' }}" 
                        {{ ($secondsRemaining ?? 0) > 0 ? 'disabled' : '' }}>
                    <svg id="resend-icon-timer" width="14" height="14" fill="currentColor" viewBox="0 0 24 24" style="{{ ($secondsRemaining ?? 0) > 0 ? 'display: inline;' : 'display: none;' }}">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                    <svg id="resend-icon-refresh" width="14" height="14" fill="currentColor" viewBox="0 0 24 24" style="{{ ($secondsRemaining ?? 0) > 0 ? 'display: none;' : 'display: inline;' }}">
                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                    </svg>
                    <span id="resend-text">
                        @if(($secondsRemaining ?? 0) > 0)
                            Resend Code ({{ $secondsRemaining }}s)
                        @else
                            Resend Code
                        @endif
                    </span>
                </button>
            </form>
        </div>

        <script>
            (function() {
                var secondsLeft = {{ (int) ($secondsRemaining ?? 0) }};
                var btn = document.getElementById('btn-resend-otp');
                var textSpan = document.getElementById('resend-text');
                var iconTimer = document.getElementById('resend-icon-timer');
                var iconRefresh = document.getElementById('resend-icon-refresh');
                var form = document.getElementById('form-resend-otp');
                var timer = null;

                function updateUI() {
                    if (!btn || !textSpan) return;
                    if (secondsLeft > 0) {
                        btn.disabled = true;
                        btn.style.cursor = 'not-allowed';
                        btn.style.color = '#94a3b8';
                        btn.style.textDecoration = 'none';
                        if (iconTimer) iconTimer.style.display = 'inline';
                        if (iconRefresh) iconRefresh.style.display = 'none';
                        textSpan.textContent = 'Resend Code (' + secondsLeft + 's)';
                    } else {
                        btn.disabled = false;
                        btn.style.cursor = 'pointer';
                        btn.style.color = '#3182ce';
                        btn.style.textDecoration = 'underline';
                        if (iconTimer) iconTimer.style.display = 'none';
                        if (iconRefresh) iconRefresh.style.display = 'inline';
                        textSpan.textContent = 'Resend Code';
                        if (timer) {
                            clearInterval(timer);
                            timer = null;
                        }
                    }
                }

                updateUI();

                if (secondsLeft > 0) {
                    timer = setInterval(function() {
                        secondsLeft--;
                        if (secondsLeft <= 0) {
                            secondsLeft = 0;
                            updateUI();
                        } else {
                            updateUI();
                        }
                    }, 1000);
                }

                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (secondsLeft > 0) {
                            e.preventDefault();
                            return false;
                        }
                        if (btn) {
                            btn.disabled = true;
                            btn.style.cursor = 'wait';
                            btn.style.color = '#94a3b8';
                            if (textSpan) textSpan.textContent = 'Sending...';
                        }
                    });
                }
            })();
        </script>
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
