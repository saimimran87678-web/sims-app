<div>
    @php
        $logoPath = \App\Models\Setting::getGlobal('institute_logo');
        $instituteName = \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2');
    @endphp

    <div class="glass-card p-8">
        {{-- School Logo & Title (Identical to Main App Login) --}}
        <div class="text-center mb-8">
            <div class="school-logo" style="{{ $logoPath ? 'background: #ffffff;' : '' }}">
                @if($logoPath && file_exists(public_path($logoPath)))
                    <img src="{{ '/' . $logoPath }}" style="width: 100%; height: 100%; object-fit: contain; padding: 6px; border-radius: 20px;" alt="{{ $instituteName }}">
                @else
                    <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                        <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                    </svg>
                @endif
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e3a5f; margin: 0;">{{ $instituteName }}</h1>
        </div>

        {{-- Role / Portal Segmented Toggle --}}
        <div style="display: flex; background: #f1f5f9; padding: 4px; border-radius: 14px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
            <a href="{{ route('login') }}" style="flex: 1; text-align: center; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; color: #64748b; text-decoration: none; transition: all 0.2s;">
                Staff / Teacher
            </a>
            <div style="flex: 1; text-align: center; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 700; background: #ffffff; color: #1e3a5f; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                Student / Parent
            </div>
        </div>

        {{-- Error Banner --}}
        @if($errorMessage)
            <div style="margin-bottom: 20px; padding: 12px 16px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; color: #b91c1c; font-size: 13px; display: flex; align-items: flex-start; gap: 10px;">
                <svg style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        <form wire:submit.prevent="login">
            {{-- Father / Guardian CNIC Input --}}
            <div style="margin-bottom: 20px;" x-data="{
                formatCNIC(e) {
                    let v = e.target.value.replace(/\D/g, '').slice(0, 13);
                    let res = v;
                    if (v.length > 5) res = v.slice(0, 5) + '-' + v.slice(5);
                    if (v.length > 12) res = res.slice(0, 13) + '-' + res.slice(13, 14);
                    e.target.value = res;
                    $wire.set('father_cnic', res);
                }
            }">
                <label for="father_cnic" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Father's / Guardian's CNIC
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                    </span>
                    <input 
                        id="father_cnic"
                        type="text" 
                        class="input-modern" 
                        style="padding-left: 44px; font-family: monospace; font-size: 14px; letter-spacing: 0.5px;" 
                        wire:model.defer="father_cnic"
                        @input="formatCNIC($event)"
                        placeholder="37405-1234567-1" 
                        maxlength="15" 
                        required 
                        autofocus
                    >
                </div>
                @error('father_cnic')
                    <span style="display: block; color: #dc2626; font-size: 12px; margin-top: 6px;">{{ $message }}</span>
                @enderror
                <p style="font-size: 11px; color: #94a3b8; margin: 4px 0 0 2px;">Standard 13-digit format (XXXXX-XXXXXXX-X)</p>
            </div>

            {{-- Student Admission Number Input --}}
            <div style="margin-bottom: 24px;">
                <label for="admission_no" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    Student Admission Number
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                    <input 
                        id="admission_no" 
                        type="text" 
                        class="input-modern" 
                        style="padding-left: 44px; text-transform: uppercase;" 
                        wire:model.defer="admission_no"
                        placeholder="e.g. 3872 or ADM-2025-001" 
                        required
                    >
                </div>
                @error('admission_no')
                    <span style="display: block; color: #dc2626; font-size: 12px; margin-top: 6px;">{{ $message }}</span>
                @enderror
                <p style="font-size: 11px; color: #94a3b8; margin: 4px 0 0 2px;">Assigned student registration / admission number</p>
            </div>

            {{-- Submit Button (Matches .btn-login exactly) --}}
            <button type="submit" class="btn-login" wire:loading.attr="disabled">
                <span style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <span wire:loading.remove>Access Student Portal</span>
                    <span wire:loading>Verifying Records...</span>
                </span>
            </button>
        </form>

        {{-- Return to Staff Login Link --}}
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <a class="link-modern" style="font-size: 13px; display: inline-flex; align-items: center; gap: 6px;" href="{{ route('login') }}">
                <span>← Return to Staff & Teacher Login</span>
            </a>
        </div>
    </div>

    {{-- Footer (Matches Main App Login) --}}
    <div style="text-align: center; margin-top: 32px; display: flex; flex-direction: column; gap: 8px;">
        <p style="font-size: 12px; color: rgba(255, 255, 255, 0.7); font-weight: 500; margin: 0;">
            Powered by <strong style="color: #ffffff;">Adminova</strong> Information Management System
        </p>
        <p style="font-size: 11px; color: rgba(255, 255, 255, 0.5); margin: 0;">
            © {{ date('Y') }} All Rights Reserved.
        </p>
    </div>
</div>
