<x-guest-layout>
    @php
        $logoPath = \App\Models\Setting::getGlobal('institute_logo');
        $instituteName = \App\Models\Setting::getGlobal('institute_name', 'SIMS');
        $user = $user ?? auth()->user();
        $email = $user ? $user->email : '';
        $obfuscatedEmail = '';
        if ($email && str_contains($email, '@')) {
            [$name, $domain] = explode('@', $email);
            $obfuscatedEmail = (strlen($name) <= 2) 
                ? substr($name, 0, 1) . '*' . '@' . $domain
                : substr($name, 0, 1) . str_repeat('*', max(1, strlen($name) - 2)) . substr($name, -1) . '@' . $domain;
        } else {
            $obfuscatedEmail = $email;
        }
    @endphp

    <div class="glass-card p-8">
        {{-- Institute Header --}}
        <div class="text-center mb-6">
            <div class="school-logo" style="{{ $logoPath ? 'background: #ffffff;' : '' }}">
                @if($logoPath && file_exists(public_path($logoPath)))
                    <img src="{{ '/' . $logoPath }}" style="width: 100%; height: 100%; object-fit: contain; padding: 6px; border-radius: 20px;" alt="{{ $instituteName }}">
                @else
                    <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                        <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                    </svg>
                @endif
            </div>
            <h1 style="font-size: 20px; font-weight: 700; color: #1e3a5f; margin: 0;">{{ $instituteName }}</h1>
        </div>

        {{-- Lock Status Banner --}}
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #f59e0b; border-radius: 14px; padding: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #d97706; box-shadow: 0 1px 3px rgba(0,0,0,0.1); shrink-0;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: #92400e;">Session Locked</h4>
                <p style="margin: 0; font-size: 11px; color: #b45309;">Your session is protected due to inactivity. Enter password to resume.</p>
            </div>
        </div>

        {{-- User Identity Card --}}
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; font-size: 24px; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);">
                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
            </div>
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">{{ $user->name ?? 'Authenticated User' }}</h3>
            <span style="font-size: 12px; color: #64748b; font-family: monospace;">{{ $obfuscatedEmail }}</span>
        </div>

        {{-- Unlock Form --}}
        <form method="POST" action="{{ route('session.unlock') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            {{-- Password --}}
            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Account Password
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; z-index: 10;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    </span>
                    <input id="password" type="password" class="input-modern" style="padding-left: 44px; padding-right: 44px;" 
                           name="password" required autofocus autocomplete="current-password" placeholder="Enter your password to unlock">
                    <button type="button" onclick="togglePasswordVisibility('password', this)" 
                            aria-label="Toggle password visibility"
                            style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; z-index: 10;">
                        <svg class="eye-show" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        <svg class="eye-hide" style="display: none;" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            {{-- Submit Button --}}
            <button type="submit" id="btn-unlock-submit" class="btn-login" style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);">
                <span id="btn-unlock-content" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    Unlock Session
                </span>
            </button>
        </form>

        <script>
            function togglePasswordVisibility(inputId, btn) {
                var input = document.getElementById(inputId);
                if (!input) return;
                var isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                var eyeShow = btn.querySelector('.eye-show');
                var eyeHide = btn.querySelector('.eye-hide');
                if (eyeShow && eyeHide) {
                    eyeShow.style.display = isPass ? 'none' : 'block';
                    eyeHide.style.display = isPass ? 'block' : 'none';
                }
            }

            document.querySelector('form')?.addEventListener('submit', function() {
                var btn = document.getElementById('btn-unlock-submit');
                var content = document.getElementById('btn-unlock-content');
                if (btn && content) {
                    btn.style.opacity = '0.85';
                    btn.style.pointerEvents = 'none';
                    content.innerHTML = '<svg style="width: 18px; height: 18px; animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Unlocking...</span>';
                }
            });
        </script>

        {{-- Switch Account / Log Out Option --}}
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
            <span style="color: #64748b;">Not you?</span>
            <a class="link-modern" style="color: #e11d48; font-weight: 600;" href="{{ route('logout.get') }}">
                Log out & switch user
            </a>
        </div>
    </div>

    {{-- Footer --}}
    <div style="text-align: center; margin-top: 32px; display: flex; flex-direction: column; gap: 8px;">
        <p style="font-size: 12px; color: rgba(255, 255, 255, 0.7); font-weight: 500; margin: 0;">
            Protected by <strong style="color: #ffffff;">Adminova SIMS</strong> Security Architecture
        </p>
    </div>
</x-guest-layout>
