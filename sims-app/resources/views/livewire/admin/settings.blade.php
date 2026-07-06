<div class="space-y-6 max-w-2xl mx-auto">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.dashboard') }}" class="p-2 bg-white border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">System Settings</h1>
            <p class="text-sm font-medium text-gray-500 mt-0.5">Configure global configurations for your Adminova instance</p>
        </div>
    </div>

    {{-- Success Message --}}
    @if (session()->has('status'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('status') }}
        </div>
    @endif

    {{-- Settings Form Card --}}
    <div class="glass-card p-8 rounded-2xl bg-white shadow-sm border border-gray-100">
        <form wire:submit.prevent="save" class="space-y-8">

            {{-- ── Section: Branding ── --}}
            <div class="space-y-6">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Branding & Name Variations
                </h2>

                <div>
                    <label for="institute_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Standard Institute Name
                    </label>
                    <input
                        type="text"
                        id="institute_name"
                        wire:model.defer="institute_name"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-600 transition-colors bg-gray-50 focus:bg-white text-gray-800"
                        placeholder="e.g. Islamabad Model College for Boys"
                        required
                    >
                    <p class="text-xs text-gray-400 mt-2">
                        Used for standard UI headings, sidebars, dashboard metrics, and general system references.
                    </p>
                    @error('institute_name')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="institute_formal_name" class="block text-sm font-semibold text-gray-700 mb-2">
                            Formal/Full Name (for Prints)
                        </label>
                        <input
                            type="text"
                            id="institute_formal_name"
                            wire:model.defer="institute_formal_name"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-600 transition-colors bg-gray-50 focus:bg-white text-gray-800"
                            placeholder="e.g. Islamabad Model College for Boys (VI-X), G-6/2 Islamabad"
                        >
                        <p class="text-xs text-gray-400 mt-2">
                            Used on formal printouts: Datesheets, Fee Invoices, Report Cards, and Gazettes.
                        </p>
                        @error('institute_formal_name')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="institute_short_name" class="block text-sm font-semibold text-gray-700 mb-2">
                            Short Name / Abbreviation
                        </label>
                        <input
                            type="text"
                            id="institute_short_name"
                            wire:model.defer="institute_short_name"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-600 transition-colors bg-gray-50 focus:bg-white text-gray-800"
                            placeholder="e.g. IMCB"
                        >
                        <p class="text-xs text-gray-400 mt-2">
                            Used for character-limited contexts: WhatsApp alerts, SMS notifications, and mobile views.
                        </p>
                        @error('institute_short_name')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Institute Logo
                        </label>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" class="w-16 h-16 object-contain rounded-lg bg-white border border-gray-100 shadow-sm">
                            @elseif ($institute_logo && file_exists(public_path($institute_logo)))
                                <img src="{{ '/' . $institute_logo }}" class="w-16 h-16 object-contain rounded-lg bg-white border border-gray-100 shadow-sm">
                            @else
                                <div class="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center text-gray-400 text-xs font-semibold">
                                    No Logo
                                </div>
                            @endif
                            
                            <div class="flex-1">
                                <input 
                                    type="file" 
                                    id="logo" 
                                    wire:model="logo" 
                                    class="hidden" 
                                    accept="image/*"
                                >
                                <label 
                                    for="logo" 
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none cursor-pointer transition-colors"
                                >
                                    Choose Logo
                                </label>
                                <p class="text-[10px] text-gray-400 mt-1">PNG, JPG or JPEG up to 1MB</p>
                            </div>
                        </div>
                        @error('logo')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="institute_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                            Institute Phone Number
                        </label>
                        <input
                            type="text"
                            id="institute_phone"
                            wire:model.defer="institute_phone"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-600 transition-colors bg-gray-50 focus:bg-white text-gray-800"
                            placeholder="e.g. +92 51 9244482"
                        >
                        <p class="text-xs text-gray-400 mt-2">
                            This phone number will be displayed on Fee Invoices, Fee Receipts, and other official templates.
                        </p>
                        @error('institute_phone')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100"></div>

            {{-- ── Section: Academic Sessions ── --}}
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Academic Sessions
                    </h2>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Manage school years, terms, or semesters. You can define start/end dates and set the active session.
                </p>
                <a href="{{ route('admin.academic-sessions') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl transition-colors font-medium border border-indigo-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    Manage Sessions
                </a>
            </div>

            <div class="border-t border-gray-100"></div>

            {{-- ── Section: School Calendar ── --}}
            <div>
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    School Calendar
                </h2>

                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    Weekend Mode
                </label>
                <p class="text-xs text-gray-400 mb-4">
                    Controls which days are treated as weekends. Affects attendance marking (blocked on weekends), attendance reports (teaching day count), and the Schedule Editor (visible day tabs).
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    {{-- Option: Saturday & Sunday --}}
                    <label
                        for="weekend_sat_sun"
                        class="relative flex items-start gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all
                            {{ $weekend_mode === 'sat_sun' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}"
                    >
                        <input
                            type="radio"
                            id="weekend_sat_sun"
                            wire:model.defer="weekend_mode"
                            value="sat_sun"
                            class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                        >
                        <div>
                            <p class="text-sm font-semibold {{ $weekend_mode === 'sat_sun' ? 'text-blue-700' : 'text-gray-700' }}">Saturday & Sunday</p>
                            <p class="text-xs text-gray-500 mt-0.5">5-day school week. Attendance blocked on both days. <span class="font-medium text-gray-600">(Default)</span></p>
                        </div>
                        @if($weekend_mode === 'sat_sun')
                            <span class="absolute top-3 right-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </span>
                        @endif
                    </label>

                    {{-- Option: Sunday Only --}}
                    <label
                        for="weekend_sun_only"
                        class="relative flex items-start gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all
                            {{ $weekend_mode === 'sun_only' ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}"
                    >
                        <input
                            type="radio"
                            id="weekend_sun_only"
                            wire:model.defer="weekend_mode"
                            value="sun_only"
                            class="mt-1 w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500"
                        >
                        <div>
                            <p class="text-sm font-semibold {{ $weekend_mode === 'sun_only' ? 'text-green-700' : 'text-gray-700' }}">Sunday Only</p>
                            <p class="text-xs text-gray-500 mt-0.5">6-day school week. Saturday is a working day — attendance can be marked and schedule is enabled.</p>
                        </div>
                        @if($weekend_mode === 'sun_only')
                            <span class="absolute top-3 right-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </span>
                        @endif
                    </label>

                </div>
                @error('weekend_mode')
                    <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="border-t border-gray-100"></div>

            {{-- ── Section: Admin Action Security ── --}}
            <div>
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z"/></svg>
                    Admin Action Security
                </h2>

                <div class="glass-card p-5 rounded-2xl bg-gray-50 border border-gray-200">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800 text-sm">Require PIN for Admin Modifications</h3>
                            <p class="text-xs text-gray-500 mt-1">Enable this to require a security PIN when editing or deleting Admin accounts. (Like 2FA for sensitive actions)</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                            <input type="checkbox" wire:model.live="admin_action_pin_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    @if($admin_action_pin_enabled)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Security PIN</label>
                            <div class="relative max-w-xs">
                                <input
                                    type="password"
                                    wire:model.defer="admin_action_pin"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-600 transition-colors bg-white text-gray-800 tracking-widest font-mono text-lg"
                                    placeholder="••••"
                                    maxlength="6"
                                >
                                <p class="text-[10px] text-gray-400 mt-1 uppercase font-semibold tracking-wider">4-6 Digit Numeric PIN</p>
                            </div>
                            @error('admin_action_pin')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>

            {{-- Save Button --}}
            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-semibold inline-flex items-center gap-2 disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="save">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Changes
                    </span>
                    <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- System Powered-By Info --}}
    <div class="glass-card p-6 rounded-2xl bg-gray-50 border border-gray-100 text-gray-500 text-sm">
        <p class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            This system runs on <strong>Adminova Information Management System</strong>. The brand/app name cannot be changed, but you can configure custom institute names above for tenant personalization.
        </p>
    </div>

    {{-- Security Verification Modal --}}
    @if($isSecurityVerificationModalOpen)
    @teleport('body')
    <div class="fixed inset-0 z-[1000] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeSecurityVerificationModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                <form wire:submit.prevent="verifySecurityAction">
                    <div class="bg-white px-6 pt-6 pb-6 rounded-t-2xl">
                        <div class="flex flex-col items-center justify-center text-center">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4 shadow-inner">
                                <svg class="h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-xl leading-6 font-bold text-gray-900">
                                Disable Admin Security
                            </h3>
                            <p class="text-sm text-gray-500 mt-2">
                                Please verify your identity to turn off the Admin Security PIN.
                            </p>
                        </div>
                        
                        {{-- Method Selector --}}
                        <div class="mt-6 flex bg-gray-100 p-1 rounded-xl">
                            <button type="button" wire:click="$set('verificationMethod', 'password')" class="flex-1 py-2 text-sm font-semibold rounded-lg transition-colors {{ $verificationMethod === 'password' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                                Account Password
                            </button>
                            <button type="button" wire:click="$set('verificationMethod', 'otp')" class="flex-1 py-2 text-sm font-semibold rounded-lg transition-colors {{ $verificationMethod === 'otp' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                                Email OTP Code
                            </button>
                        </div>

                        <div class="mt-6">
                            @if($verificationMethod === 'password')
                                <label class="block text-sm font-medium text-gray-700 text-center">Enter Password</label>
                                <div class="mt-2">
                                    <input type="password" wire:model.defer="verificationInput" class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-center" placeholder="••••••••" autofocus />
                                </div>
                            @else
                                {{-- OTP Method --}}
                                @if(!$otpSent)
                                    <div class="flex flex-col items-center gap-3">
                                        <p class="text-xs text-gray-500 text-center">We will send a 6-digit OTP to your registered email ({{ auth()->user()->email }}).</p>
                                        <button type="button" wire:click="sendVerificationOtp" wire:loading.attr="disabled" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold transition-colors">
                                            Send OTP Code
                                        </button>
                                    </div>
                                @else
                                    <label class="block text-sm font-medium text-gray-700 text-center">Enter 6-Digit OTP</label>
                                    <div class="mt-2 flex justify-center">
                                        <input type="text" wire:model.defer="verificationInput" maxlength="6" class="block w-48 text-center text-2xl tracking-widest rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="••••••" autofocus />
                                    </div>
                                    <div class="mt-3 text-center">
                                        <button type="button" wire:click="sendVerificationOtp" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            Resend OTP Code
                                        </button>
                                    </div>
                                @endif
                            @endif

                            @if($verificationError)
                                <span class="text-red-500 text-sm font-medium mt-3 block text-center">{{ $verificationError }}</span>
                            @endif

                            @if(session()->has('otp_status'))
                                <span class="text-green-600 text-xs font-semibold mt-3 block text-center">{{ session('otp_status') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 rounded-b-2xl flex justify-center gap-3">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-3 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm" {{ ($verificationMethod === 'otp' && !$otpSent) ? 'disabled' : '' }}>
                            Verify & Disable
                        </button>
                        <button type="button" wire:click="closeSecurityVerificationModal" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>
