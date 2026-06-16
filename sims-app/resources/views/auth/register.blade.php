<x-guest-layout>
    <div class="glass-card p-8">
        {{-- School Logo & Title --}}
        <div class="text-center mb-8">
            <div class="school-logo">
                <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                </svg>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e3a5f; margin: 0;">Create Account</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Join {{ \App\Models\Setting::get('institute_name', 'IMCB G-6/2') }}</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            {{-- Name --}}
            <div style="margin-bottom: 20px;">
                <label for="name" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Full Name
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </span>
                    <input id="name" class="input-modern" style="padding-left: 44px;" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Enter your full name">
                </div>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            {{-- Email Address --}}
            <div style="margin-bottom: 20px;">
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Email Address
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </span>
                    <input id="email" class="input-modern" style="padding-left: 44px;" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="Enter your email">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            {{-- Role --}}
            <div style="margin-bottom: 20px;">
                <label for="role" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Account Role
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; z-index: 10;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                    </span>
                    <select id="role" name="role" class="input-modern" style="padding-left: 44px; appearance: none; background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Cpolyline points=%226 9 12 15 18 9%22%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px;">
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            {{-- Password --}}
            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Password
                </label>
                <div style="position: relative;" x-data="{ showPassword: false }">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    </span>
                    <input id="password" class="input-modern" style="padding-left: 44px; padding-right: 44px;" 
                           :type="showPassword ? 'text' : 'password'" 
                           name="password" required autocomplete="new-password" placeholder="Create a strong password">
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

            {{-- Register Button --}}
            <button type="submit" class="btn-login">
                <span style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
                    Register Account
                </span>
            </button>
        </form>

        {{-- Login Link --}}
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <span style="font-size: 13px; color: #64748b;">Already have an account?</span>
            <a class="link-modern" style="font-size: 13px; margin-left: 4px;" href="{{ route('login') }}">
                Sign in
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
