<div class="glass-card p-6 sm:p-10 max-w-2xl mx-auto shadow-2xl border border-white/40">
    <!-- Header & Badge -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white shadow-lg mb-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight">SIMS Initial Setup</h1>
        <p class="text-sm text-slate-500 mt-1">Configure your school management system in 4 simple steps</p>

        <!-- Step Indicator -->
        <div class="flex items-center justify-center mt-6 space-x-2 sm:space-x-4">
            <!-- Step 1 -->
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $currentStep >= 1 ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-200 text-slate-500' }}">
                    @if($currentStep > 1) ✓ @else 1 @endif
                </div>
                <span class="text-xs font-semibold ml-1.5 hidden sm:inline {{ $currentStep === 1 ? 'text-indigo-600' : 'text-slate-400' }}">License</span>
            </div>
            <div class="w-6 sm:w-10 h-0.5 {{ $currentStep > 1 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>

            <!-- Step 2 -->
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $currentStep >= 2 ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-200 text-slate-500' }}">
                    @if($currentStep > 2) ✓ @else 2 @endif
                </div>
                <span class="text-xs font-semibold ml-1.5 hidden sm:inline {{ $currentStep === 2 ? 'text-indigo-600' : 'text-slate-400' }}">Institute</span>
            </div>
            <div class="w-6 sm:w-10 h-0.5 {{ $currentStep > 2 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>

            <!-- Step 3 -->
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $currentStep >= 3 ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-200 text-slate-500' }}">
                    @if($currentStep > 3) ✓ @else 3 @endif
                </div>
                <span class="text-xs font-semibold ml-1.5 hidden sm:inline {{ $currentStep === 3 ? 'text-indigo-600' : 'text-slate-400' }}">Shifts</span>
            </div>
            <div class="w-6 sm:w-10 h-0.5 {{ $currentStep > 3 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>

            <!-- Step 4 -->
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $currentStep >= 4 ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-200 text-slate-500' }}">
                    4
                </div>
                <span class="text-xs font-semibold ml-1.5 hidden sm:inline {{ $currentStep === 4 ? 'text-indigo-600' : 'text-slate-400' }}">Admin</span>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────────────────────────────── -->
    <!-- STEP 1: LICENSE ACTIVATION (GATEKEEPER)                            -->
    <!-- ────────────────────────────────────────────────────────────────── -->
    @if($currentStep === 1)
        <div class="space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Step 1: License Activation
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Please enter the official license key provided with your software invoice or welcome email.</p>
            </div>

            @if($license_error)
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <span class="font-bold">Activation Notice:</span> {{ $license_error }}
                    </div>
                </div>
            @endif

            @if($license_verified)
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs space-y-2">
                    <div class="flex items-center gap-2 font-bold text-emerald-900">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        License Verified & Active
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-emerald-200/60">
                        <div><span class="text-emerald-600 font-semibold">School ID:</span> {{ $license_details['school_id'] ?? 'N/A' }}</div>
                        <div><span class="text-emerald-600 font-semibold">Plan Tier:</span> <span class="px-1.5 py-0.5 bg-emerald-200 rounded font-bold">{{ $license_details['plan'] ?? 'STANDARD' }}</span></div>
                        <div><span class="text-emerald-600 font-semibold">Status:</span> {{ $license_details['status'] ?? 'Active' }}</div>
                        <div><span class="text-emerald-600 font-semibold">Valid Until:</span> {{ $license_details['expires_at'] ?? 'Lifetime' }}</div>
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Software License Key</label>
                <input type="text" wire:model="license_key" placeholder="e.g. SIMS-IMCB-1781541807190" class="input-modern font-mono text-sm uppercase tracking-wider" {{ $license_verified ? 'disabled' : '' }}>
                @error('license_key') <span class="text-xs text-red-500 font-medium mt-1 block">{{ $message }}</span> @enderror
                <p class="text-[11px] text-slate-400 mt-2">Connecting to licensing servers requires an active internet connection on this first boot.</p>
            </div>

            <div class="pt-2">
                @if(!$license_verified)
                    <button type="button" wire:click="verifyLicense" wire:loading.attr="disabled" class="btn-login flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="verifyLicense">Verify & Unlock Setup ➔</span>
                        <span wire:loading wire:target="verifyLicense" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Verifying Cryptographic Signature...
                        </span>
                    </button>
                @else
                    <button type="button" wire:click="$set('currentStep', 2)" class="btn-login flex items-center justify-center gap-2">
                        Proceed to Institute Details ➔
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- ────────────────────────────────────────────────────────────────── -->
    <!-- STEP 2: INSTITUTE PROFILE & BRANDING                               -->
    <!-- ────────────────────────────────────────────────────────────────── -->
    @if($currentStep === 2)
        <div class="space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Step 2: Institute Profile & Logo
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">These details will be printed on student vouchers, ID cards, and official reports.</p>
            </div>

            <!-- Logo Upload with Circular Live Preview -->
            <div class="flex items-center gap-5 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                <div class="relative flex-shrink-0">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" class="w-20 h-20 rounded-full object-cover border-4 border-indigo-600 shadow-md">
                    @else
                        <div class="w-20 h-20 rounded-full bg-indigo-100 border-2 border-dashed border-indigo-300 flex items-center justify-center text-indigo-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Institute Logo</label>
                    <input type="file" wire:model="logo" accept="image/*" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Recommended: Square PNG/JPG with transparent background (Max 2MB)</p>
                    @error('logo') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Institute Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="institute_name" placeholder="e.g. IMCB G-6/2" class="input-modern text-sm">
                    @error('institute_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Short Code / Acronym</label>
                    <input type="text" wire:model="institute_short_name" placeholder="e.g. IMCB" class="input-modern text-sm">
                    @error('institute_short_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Formal / Legal Title (Printed on Certificates)</label>
                <input type="text" wire:model="institute_formal_name" placeholder="e.g. Islamabad Model College for Boys, G-6/2 Islamabad" class="input-modern text-sm">
                @error('institute_formal_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Official Contact Phone</label>
                <input type="text" wire:model="institute_phone" placeholder="e.g. 051-9201234" class="input-modern text-sm">
                @error('institute_phone') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" wire:click="$set('currentStep', 1)" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    ⬅ Back to License
                </button>
                <button type="button" wire:click="goToStep3" class="btn-login !w-auto px-6">
                    Next: Shift & Academic Setup ➔
                </button>
            </div>
        </div>
    @endif

    <!-- ────────────────────────────────────────────────────────────────── -->
    <!-- STEP 3: SHIFT & ACADEMIC STRUCTURE                                 -->
    <!-- ────────────────────────────────────────────────────────────────── -->
    @if($currentStep === 3)
        <div class="space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Step 3: Shifts & Operational Policy
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Configure operational schedules and academic year boundaries.</p>
            </div>

            <!-- Shift Mode Radio Cards -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Shift Architecture</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3 {{ $shift_mode === 'Regular' ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300' }}">
                        <input type="radio" wire:model="shift_mode" value="Regular" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="block text-sm font-bold text-slate-800">Regular Shift</span>
                            <span class="text-xs text-slate-500 mt-0.5 block">Standard single morning shift operations (8:00 AM – 1:30 PM).</span>
                        </div>
                    </label>

                    <label class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3 {{ $shift_mode === 'Dual' ? 'border-indigo-600 bg-indigo-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300' }}">
                        <input type="radio" wire:model="shift_mode" value="Dual" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="block text-sm font-bold text-slate-800">Dual Shift (Morning & Evening)</span>
                            <span class="text-xs text-slate-500 mt-0.5 block">Independent morning and evening rosters, student quotas, and timetables.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Weekend Mode -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Weekly Weekend Policy</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="p-3.5 rounded-xl border cursor-pointer flex items-center gap-3 {{ $weekend_mode === 'sat_sun' ? 'border-indigo-600 bg-indigo-50/40' : 'border-slate-200' }}">
                        <input type="radio" wire:model="weekend_mode" value="sat_sun" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-semibold text-slate-700">Saturday & Sunday Off (5-Day Week)</span>
                    </label>
                    <label class="p-3.5 rounded-xl border cursor-pointer flex items-center gap-3 {{ $weekend_mode === 'sun_only' ? 'border-indigo-600 bg-indigo-50/40' : 'border-slate-200' }}">
                        <input type="radio" wire:model="weekend_mode" value="sun_only" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-semibold text-slate-700">Sunday Only Off (6-Day Week)</span>
                    </label>
                </div>
            </div>

            <!-- Initial Session Name -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Initial Academic Session</label>
                <input type="text" wire:model="session_name" placeholder="e.g. 2026-2027" class="input-modern text-sm font-semibold">
                @error('session_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                <p class="text-[11px] text-slate-400 mt-1">This will be created as your primary active academic session.</p>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" wire:click="$set('currentStep', 2)" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    ⬅ Back to Institute Profile
                </button>
                <button type="button" wire:click="goToStep4" class="btn-login !w-auto px-6">
                    Next: Administrator Account ➔
                </button>
            </div>
        </div>
    @endif

    <!-- ────────────────────────────────────────────────────────────────── -->
    <!-- STEP 4: SUPER ADMIN ACCOUNT CREATION                               -->
    <!-- ────────────────────────────────────────────────────────────────── -->
    @if($currentStep === 4)
        <div class="space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Step 4: Create Super Admin Account
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">This master account will possess unconstrained global administrative authority across the entire school.</p>
            </div>

            @if($setup_error)
                <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
                    {{ $setup_error }}
                </div>
            @endif

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Principal / Administrator Full Name <span class="text-red-500">*</span></label>
                <input type="text" wire:model="admin_name" placeholder="e.g. Saim Imran" class="input-modern text-sm">
                @error('admin_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Official Login Email <span class="text-red-500">*</span></label>
                <input type="email" wire:model="admin_email" placeholder="e.g. principal@school.edu.pk" class="input-modern text-sm">
                @error('admin_email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Master Password <span class="text-red-500">*</span></label>
                    <input type="password" wire:model="admin_password" placeholder="At least 8 characters" class="input-modern text-sm">
                    @error('admin_password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" wire:model="admin_password_confirmation" placeholder="Repeat password" class="input-modern text-sm">
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-[11px] leading-relaxed">
                <span class="font-bold">Important Security Note:</span> Once this master Super Admin account is created, the setup wizard will be permanently locked. You will be logged in immediately.
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" wire:click="$set('currentStep', 3)" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    ⬅ Back to Shifts
                </button>
                <button type="button" wire:click="finishSetup" wire:loading.attr="disabled" class="btn-login !w-auto px-8 !bg-gradient-to-r !from-emerald-600 !to-teal-600">
                    <span wire:loading.remove wire:target="finishSetup">🚀 Complete Installation & Launch SIMS</span>
                    <span wire:loading wire:target="finishSetup" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Initializing Institute System...
                    </span>
                </button>
            </div>
        </div>
    @endif
</div>
