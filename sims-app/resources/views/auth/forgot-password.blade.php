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
                        <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                    </svg>
                @endif
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e3a5f; margin: 0;">Forgot Password</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Reset password for {{ \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2') }}</p>
        </div>

        <div style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 24px; text-align: center;">
            {{ __('Forgot your password? No problem. Enter your email and we will send you a 6-digit verification code (OTP) to reset your password.') }}
        </div>

        {{-- Session Status --}}
        <x-auth-session-status class="mb-4" :status="session('status')" />

        @php
            $secondsRemaining = (int) ($secondsRemaining ?? session('secondsRemaining', 0));
            $hasCooldown = $secondsRemaining > 0;
        @endphp

        {{-- Live Countdown Alert if throttled --}}
        <div id="cooldown-banner" class="{{ $hasCooldown ? '' : 'hidden' }}" style="padding: 12px 14px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; color: #b91c1c; font-size: 13px; text-align: center; margin-bottom: 20px; display: {{ $hasCooldown ? 'flex' : 'none' }}; align-items: center; justify-content: center; gap: 8px;">
            <svg style="width: 16px; height: 16px; color: #ef4444; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Please wait <strong id="countdown-seconds" style="font-family: monospace; font-size: 14px;">{{ $secondsRemaining }}</strong> second(s) before requesting a new OTP.</span>
        </div>

        <form id="form-forgot-password" method="POST" action="{{ route('password.email') }}">
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
                    <input id="email" class="input-modern" style="padding-left: 44px;" type="email" name="email" value="{{ old('email', session('reset_email')) }}" required autofocus placeholder="Enter your email">
                </div>
                @if(!$hasCooldown)
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                @endif
            </div>

            {{-- Send Code Button with Double-Click Protection & Spinner --}}
            <button type="submit" id="btn-submit-reset" class="btn-login" {{ $hasCooldown ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                <span id="btn-reset-content" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    <span id="btn-reset-text">{{ $hasCooldown ? "Please Wait ({$secondsRemaining}s)" : "Send Reset Code" }}</span>
                </span>
            </button>
        </form>

        {{-- Script for Live Dynamic Countdown and Double-Click Protection --}}
        <script>
            (function() {
                var form = document.getElementById('form-forgot-password');
                var btn = document.getElementById('btn-submit-reset');
                var content = document.getElementById('btn-reset-content');
                var textSpan = document.getElementById('btn-reset-text');
                var banner = document.getElementById('cooldown-banner');
                var countSpan = document.getElementById('countdown-seconds');
                var secondsLeft = {{ (int) $secondsRemaining }};
                var isSubmitting = false;

                // 1. Live Countdown Timer
                if (secondsLeft > 0) {
                    var interval = setInterval(function() {
                        secondsLeft--;
                        if (secondsLeft <= 0) {
                            clearInterval(interval);
                            if (banner) {
                                banner.style.display = 'none';
                            }
                            if (btn) {
                                btn.disabled = false;
                                btn.style.opacity = '1';
                                btn.style.cursor = 'pointer';
                            }
                            if (textSpan) {
                                textSpan.textContent = 'Send Reset Code';
                            }
                        } else {
                            if (countSpan) countSpan.textContent = secondsLeft;
                            if (textSpan) textSpan.textContent = 'Please Wait (' + secondsLeft + 's)';
                        }
                    }, 1000);
                }

                // 2. High-speed AJAX submit with instant UI transition and double-click prevention
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (secondsLeft > 0 || isSubmitting) {
                            e.preventDefault();
                            return false;
                        }

                        e.preventDefault();
                        isSubmitting = true;
                        if (btn) {
                            btn.disabled = true;
                            btn.style.opacity = '0.75';
                            btn.style.cursor = 'wait';
                            btn.style.pointerEvents = 'none';
                        }
                        if (content) {
                            content.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Sending Reset Code...</span>';
                        }

                        var formData = new FormData(form);
                        fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(function(res) {
                            var contentType = res.headers.get('content-type') || '';
                            if (contentType.includes('application/json')) {
                                return res.json().then(function(data) {
                                    if (res.ok) {
                                        window.location.href = data.redirect || '{{ route("password.otp.verify") }}';
                                    } else {
                                        throw data;
                                    }
                                });
                            } else {
                                if (res.ok) {
                                    window.location.href = '{{ route("password.otp.verify") }}';
                                } else {
                                    throw new Error('A server error occurred. Please try again.');
                                }
                            }
                        })
                        .catch(function(err) {
                            isSubmitting = false;
                            if (btn) {
                                btn.disabled = false;
                                btn.style.opacity = '1';
                                btn.style.cursor = 'pointer';
                                btn.style.pointerEvents = 'auto';
                            }
                            if (content) {
                                content.innerHTML = '<svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg> <span id="btn-reset-text">Send Reset Code</span>';
                                textSpan = document.getElementById('btn-reset-text');
                            }
                            var errorMsg = (err && err.message) || (err && err.errors && err.errors.email ? err.errors.email[0] : null) || 'An error occurred. Please try again.';
                            if (banner && countSpan && (errorMsg.includes('second(s)') || (err && err.secondsRemaining))) {
                                var seconds = (err && err.secondsRemaining) ? parseInt(err.secondsRemaining) : null;
                                if (!seconds) {
                                    var match = errorMsg.match(/(\d+)\s*second/);
                                    if (match) seconds = parseInt(match[1]);
                                }
                                if (seconds) {
                                    secondsLeft = seconds;
                                    banner.style.display = 'flex';
                                    countSpan.textContent = secondsLeft;
                                    if (btn) {
                                        btn.disabled = true;
                                        btn.style.opacity = '0.6';
                                        btn.style.cursor = 'not-allowed';
                                    }
                                    if (textSpan) textSpan.textContent = 'Please Wait (' + secondsLeft + 's)';
                                    return;
                                }
                            }
                            alert(errorMsg);
                        });
                    });
                }
            })();
        </script>

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
