<x-guest-layout>
    <div class="glass-card p-8">
        {{-- School Logo & Title --}}
        <div class="text-center mb-8">
            <div class="school-logo">
                <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                    <path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM8.9 6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2H8.9V6zM18 20H6V10h12v10z"/>
                </svg>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e3a5f; margin: 0;">New Password</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Set your new account password</p>
        </div>

        <div style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 24px; text-align: center;">
            Resetting password for:<br>
            <strong style="color: #1e3a5f;">{{ session('reset_email') }}</strong>
        </div>

        {{-- Status Notification --}}
        @if (session('status'))
            <div style="padding: 10px; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); border-radius: 8px; color: #15803d; font-size: 13px; text-align: center; margin-bottom: 20px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            {{-- Password --}}
            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    New Password
                </label>
                <div style="position: relative;" x-data="{ showPassword: false }">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    </span>
                    <input id="password" class="input-modern" style="padding-left: 44px; padding-right: 44px;" 
                           :type="showPassword ? 'text' : 'password'" 
                           name="password" required autocomplete="new-password" placeholder="Enter new password">
                    <button type="button" @click="showPassword = !showPassword" 
                            style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; background: none; border: none; cursor: pointer; padding: 0;">
                        <svg x-show="!showPassword" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        <svg x-show="showPassword" x-cloak width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            {{-- Confirm Password --}}
            <div style="margin-bottom: 24px;">
                <label for="password_confirmation" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Confirm Password
                </label>
                <div style="position: relative;" x-data="{ showConfirmPassword: false }">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    </span>
                    <input id="password_confirmation" class="input-modern" style="padding-left: 44px; padding-right: 44px;" 
                           :type="showConfirmPassword ? 'text' : 'password'" 
                           name="password_confirmation" required autocomplete="new-password" placeholder="Confirm your password">
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" 
                            style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; background: none; border: none; cursor: pointer; padding: 0;">
                        <svg x-show="!showConfirmPassword" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        <svg x-show="showConfirmPassword" x-cloak width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            {{-- Reset Button --}}
            <button type="submit" class="btn-login">
                <span style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 10h3l-4 4-4-4h3V8h2v4z"/></svg>
                    Reset Password
                </span>
            </button>
        </form>
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
