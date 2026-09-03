<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Page Header & Shifter Style Tab Navigation -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-5">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-9 h-9 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-xl shadow-sm">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.003 5.324 5.328 0 11.859 0c3.166.001 6.141 1.233 8.377 3.469 2.235 2.235 3.466 5.21 3.466 8.377-.003 6.534-5.328 11.859-11.859 11.859-1.996-.001-3.957-.503-5.707-1.46L0 24zm5.835-4.265c1.62.962 3.218 1.488 4.931 1.49 5.373 0 9.742-4.369 9.745-9.743 0-2.602-1.012-5.05-2.849-6.888C15.83 2.756 13.38 1.745 10.781 1.745c-5.372 0-9.742 4.37-9.745 9.743-.001 1.83.491 3.58 1.42 5.176l-.991 3.616 3.7-.971zm13.14-8.122c-.27-.135-1.597-.787-1.845-.877-.247-.09-.427-.135-.607.135-.18.27-.697.877-.855 1.057-.158.18-.315.202-.585.067-.27-.135-1.139-.42-2.17-1.34-.801-.715-1.343-1.6-1.5-1.871-.158-.27-.017-.417.118-.552.122-.122.27-.315.405-.472.135-.158.18-.27.27-.45.09-.18.045-.337-.022-.472-.067-.135-.607-1.462-.832-2.002-.22-.53-.442-.457-.607-.466-.158-.008-.338-.01-.518-.01-.18 0-.472.067-.72.338-.247.27-.945.922-.945 2.25s.967 2.61 1.102 2.79c.135.18 1.902 2.904 4.609 4.073.644.279 1.147.445 1.54.57.647.206 1.236.177 1.701.108.518-.077 1.598-.652 1.823-1.282.225-.63.225-1.17.157-1.282-.068-.113-.248-.18-.518-.315z"/></svg>
                </span>
                WhatsApp Manager
            </h1>
            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Centralized WhatsApp controls & message automation</span>
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-700/60 shadow-xs">
                    <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Session: {{ \App\Models\AcademicSession::find(\App\Models\AcademicSession::getActiveSessionId())->name ?? 'Active Session' }} 
                    <span class="opacity-60">|</span>
                    {{ ucfirst(session('selected_shift_type', 'morning')) }} Shift
                </span>
            </div>
        </div>
        
        <!-- Tab Shifter -->
        <div class="overflow-x-auto max-w-full -mx-4 px-4 md:mx-0 md:px-0 self-start md:self-auto shrink-0 scrollbar-none">
            <div class="flex bg-gray-100 dark:bg-gray-800/80 p-1 rounded-xl border border-gray-200 dark:border-gray-700/60 shrink-0 shadow-inner min-w-[350px]">
                <button 
                    wire:click="$set('activeTab', 'setup')" 
                    class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'setup' ? 'bg-white dark:bg-gray-700 text-purple-600 dark:text-purple-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    WhatsApp Setup
                </button>
                <button 
                    wire:click="$set('activeTab', 'queue')" 
                    class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'queue' ? 'bg-white dark:bg-gray-700 text-purple-600 dark:text-purple-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Queue Manager
                </button>
                <button 
                    wire:click="$set('activeTab', 'templates')" 
                    class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'templates' ? 'bg-white dark:bg-gray-700 text-purple-600 dark:text-purple-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Message Templates
                </button>
            </div>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if (session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between shadow-sm animate-in fade-in duration-200" x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-bold">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 flex items-center justify-between shadow-sm animate-in fade-in duration-200" x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-bold">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    @endif

    {{-- TAB 1: WHATSAPP SETUP --}}
    @if($activeTab === 'setup')
        <div class="space-y-6 animate-in fade-in duration-200">
            <div class="glass-card p-6 sm:p-10 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="text-center space-y-6 max-w-xl mx-auto">
                    
                    {{-- Connection Status --}}
                    @if($isConnected)
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full border border-emerald-200 dark:border-emerald-800 shadow-xs">
                            <span class="w-3.5 h-3.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            <span class="font-extrabold text-lg">WhatsApp Web Connected</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 text-sm font-medium">WhatsApp web session is active and linked to the automated notification daemon.</p>
                        <p class="text-xs text-gray-400">Parent fee reminders, attendance alerts, and payment receipts will be dispatched automatically.</p>
                        
                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 flex flex-wrap justify-center gap-3">
                            <button 
                                wire:click="refreshStatus"
                                wire:loading.attr="disabled"
                                class="px-5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Refresh Connection Status
                            </button>
                            <button 
                                wire:click="logout"
                                wire:confirm="Are you sure you want to disconnect WhatsApp? You will need to scan the QR code again."
                                class="px-5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 dark:text-rose-300 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                Disconnect Session
                            </button>
                        </div>
                    @elseif($qrData)
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-full border border-amber-200 dark:border-amber-800 shadow-xs">
                            <span class="w-3.5 h-3.5 bg-amber-500 rounded-full animate-pulse"></span>
                            <span class="font-extrabold text-lg">Scan QR Code to Link Device</span>
                        </div>
                        
                        <div class="bg-white p-4 rounded-2xl inline-block shadow-md border border-gray-200">
                            <img src="{{ $qrData }}" alt="WhatsApp QR Code" class="w-64 h-64 mx-auto" />
                        </div>
                        
                        <div class="space-y-2 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-200 dark:border-gray-600 max-w-sm mx-auto text-left">
                            <p class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-300">Linking Instructions:</p>
                            <ol class="text-xs text-gray-600 dark:text-gray-300 space-y-1.5 font-medium">
                                <li>1. Open WhatsApp on your mobile phone</li>
                                <li>2. Tap Menu (⋮) or Settings &rarr; <strong>Linked Devices</strong></li>
                                <li>3. Tap <strong>"Link a Device"</strong></li>
                                <li>4. Point your camera at this QR code</li>
                            </ol>
                        </div>
                    @elseif($errorMessage)
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-full border border-rose-200 dark:border-rose-800">
                            <span class="w-3.5 h-3.5 bg-rose-500 rounded-full"></span>
                            <span class="font-extrabold text-lg">WhatsApp Service Offline</span>
                        </div>
                        <p class="text-rose-600 dark:text-rose-400 font-semibold text-sm">{{ $errorMessage }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Please verify that the background Node.js WhatsApp service is running on port 3000.</p>
                    @else
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
                            <span class="w-3.5 h-3.5 bg-gray-400 rounded-full animate-pulse"></span>
                            <span class="font-extrabold text-lg">Checking WhatsApp Connection...</span>
                        </div>
                        <p class="text-xs text-gray-500">Checking service health and QR status...</p>
                    @endif

                    <div class="pt-2">
                        <button 
                            wire:click="refreshStatus"
                            wire:loading.attr="disabled"
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-xs transition-colors inline-flex items-center gap-2 shadow-sm"
                        >
                            <span wire:loading.remove wire:target="refreshStatus" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Refresh Connection Status
                            </span>
                            <span wire:loading wire:target="refreshStatus" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Checking...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 2: QUEUE MANAGER --}}
    @if($activeTab === 'queue')
        <div class="glass-card p-6 sm:p-7 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm animate-in fade-in duration-200" wire:poll.15s>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Message Queue Dispatcher</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Monitor and control automated fee reminders, attendance, and receipt notifications</p>
                </div>
            </div>
            
            {{-- Queue Auto-Send Settings Panel --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-600 mb-6">
                <h4 class="font-bold text-gray-800 dark:text-gray-200 text-xs uppercase tracking-wider mb-3">Auto-Send Configuration</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">Queue Processor</label>
                        <div class="flex items-center h-10" x-data="{ enabled: @entangle('autoSendEnabled') }">
                            <label class="relative inline-flex items-center cursor-pointer" title="Enable to start auto-processing messages">
                                <input type="checkbox" x-model="enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                <span class="ml-2.5 text-xs font-bold text-gray-700 dark:text-gray-300" x-text="enabled ? 'Enabled' : 'Disabled'">{{ $autoSendEnabled ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">Start Window</label>
                        <input type="time" wire:model="autoSendStart" class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">End Window</label>
                        <input type="time" wire:model="autoSendEnd" class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">Delay (Seconds)</label>
                        <input type="number" min="3" max="60" wire:model="queueDelay" class="w-full text-xs font-medium rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-600 uppercase tracking-wider mb-1">Force Dispatch</label>
                        <div class="flex items-center h-10" x-data="{ force: @entangle('forceSendNow') }">
                            <label class="relative inline-flex items-center cursor-pointer" title="Bypass Start/End window restrictions and send immediately">
                                <input type="checkbox" x-model="force" class="sr-only peer">
                                <div class="w-11 h-6 bg-red-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-red-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                <span class="ml-2.5 text-xs font-extrabold text-red-600" x-text="force ? 'Active' : 'Off'">{{ $forceSendNow ? 'Active' : 'Off' }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="md:col-span-5 flex justify-end items-center gap-3 pt-2">
                        <button wire:click="saveSettings" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors text-xs font-bold shadow-sm inline-flex items-center gap-2">
                            <span wire:loading.remove wire:target="saveSettings">Save Auto-Send Settings</span>
                            <span wire:loading wire:target="saveSettings" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Filters & Search Bar --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-4">
                <div class="w-full sm:w-72">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search by student name, roll, phone..." 
                        class="w-full px-3.5 py-2 text-xs font-medium rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 focus:bg-white transition-all"
                    >
                </div>
                <div class="w-full sm:w-48">
                    <select 
                        wire:model.live="filterStatus" 
                        class="w-full px-3.5 py-2 text-xs font-medium rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all"
                    >
                        <option value="">All Message Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                        <option value="paused">Paused</option>
                    </select>
                </div>
            </div>

            {{-- Queue Table --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/60">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Student & Class</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Phone / Contact</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Message Content</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Queued Time</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sent Time</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($queue as $msg)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/50 transition-colors">
                                {{-- Student & Class Details --}}
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($msg->student_name)
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ $msg->student_name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                            @if($msg->class_name)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                                    {{ $msg->class_name }}
                                                </span>
                                            @endif
                                            @if($msg->roll_no)
                                                <span class="font-medium">Roll: {{ $msg->roll_no }}</span>
                                            @elseif($msg->admission_no)
                                                <span class="font-medium">Admn: {{ $msg->admission_no }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="font-semibold text-gray-600 dark:text-gray-400 text-xs italic">System Notification</div>
                                    @endif
                                </td>

                                {{-- Phone --}}
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="text-xs font-mono font-bold text-gray-800 dark:text-gray-200 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-md">
                                        {{ $msg->phone }}
                                    </span>
                                </td>

                                {{-- Message Content Preview --}}
                                <td class="px-4 py-3.5 max-w-xs text-xs text-gray-600 dark:text-gray-300">
                                    <div class="truncate font-medium cursor-help" title="{{ $msg->message }}">
                                        {{ \Illuminate\Support\Str::limit($msg->message, 45) }}
                                    </div>
                                    @if($msg->error_message)
                                        <div class="text-[11px] font-semibold text-rose-500 dark:text-rose-400 block mt-1 truncate" title="{{ $msg->error_message }}">
                                            Error: {{ $msg->error_message }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Queued Time --}}
                                <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-700 dark:text-gray-300">
                                    @if($msg->created_at)
                                        <div class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ \Carbon\Carbon::parse($msg->created_at)->format('d M, Y') }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($msg->created_at)->format('h:i:s A') }}
                                            <span class="text-[10px] text-purple-600 dark:text-purple-400">({{ \Carbon\Carbon::parse($msg->created_at)->diffForHumans() }})</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Sent Time --}}
                                <td class="px-4 py-3.5 whitespace-nowrap text-xs">
                                    @if($msg->status === 'sent' && $msg->updated_at)
                                        <div class="font-semibold text-emerald-700 dark:text-emerald-400">
                                            {{ \Carbon\Carbon::parse($msg->updated_at)->format('d M, Y') }}
                                        </div>
                                        <div class="text-[11px] text-emerald-600 dark:text-emerald-400">
                                            {{ \Carbon\Carbon::parse($msg->updated_at)->format('h:i:s A') }}
                                        </div>
                                    @elseif($msg->status === 'failed' && $msg->updated_at)
                                        <div class="font-semibold text-rose-600 dark:text-rose-400">
                                            Attempted: {{ \Carbon\Carbon::parse($msg->updated_at)->format('h:i A') }}
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                            In Queue
                                        </span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    @if($msg->status === 'sent')
                                        <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            Sent
                                        </span>
                                    @elseif($msg->status === 'failed')
                                        <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            Failed
                                        </span>
                                    @elseif($msg->status === 'paused')
                                        <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            Paused
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3.5 whitespace-nowrap text-right text-xs font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if(in_array($msg->status, ['pending', 'paused', 'failed']))
                                            <button 
                                                wire:click="toggleMessageStatus({{ $msg->id }})" 
                                                class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" 
                                                title="{{ $msg->status === 'paused' ? 'Resume Processing' : 'Pause Message' }}"
                                            >
                                                @if($msg->status === 'paused')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @endif
                                            </button>
                                            <button 
                                                wire:click="sendManual({{ $msg->id }})" 
                                                class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors" 
                                                title="Send Now (Immediate Trigger)"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                            </button>
                                        @endif
                                        <button 
                                            wire:click="deleteMessage({{ $msg->id }})" 
                                            onclick="return confirm('Delete this message from queue?') || event.stopImmediatePropagation()" 
                                            class="p-1.5 text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" 
                                            title="Delete Message"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                    <div class="max-w-xs mx-auto">
                                        <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        <p class="font-bold text-sm text-gray-700 dark:text-gray-300">No Messages in Queue</p>
                                        <p class="text-xs text-gray-500 mt-1">There are no WhatsApp messages matching your current filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($queue->hasPages())
                <div class="px-4 py-3.5 border-t border-gray-200 dark:border-gray-700">
                    {{ $queue->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- TAB 3: MESSAGE TEMPLATES --}}
    @if($activeTab === 'templates')
        <div class="space-y-6 animate-in fade-in duration-200">
            <!-- Dynamic Variable Legend -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-950/30 dark:to-indigo-950/30 p-5 rounded-2xl border border-purple-100 dark:border-purple-900/50 shadow-sm">
                <div class="flex items-center gap-2 text-sm font-extrabold text-purple-900 dark:text-purple-300 mb-2">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Available Message Variables (Placeholders)
                </div>
                <p class="text-xs text-purple-700 dark:text-purple-300/80 mb-3">
                    These tags will automatically be replaced with real student and fee details when messages are dispatched:
                </p>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{student_name}</span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{roll_no}</span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{date}</span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{time} <span class="text-gray-400 font-sans text-[10px]">(Late only)</span></span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{amount} <span class="text-gray-400 font-sans text-[10px]">(Payment only)</span></span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{balance} <span class="text-gray-400 font-sans text-[10px]">(Fee only)</span></span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{period} <span class="text-gray-400 font-sans text-[10px]">(Fee only)</span></span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{due_date} <span class="text-gray-400 font-sans text-[10px]">(Reminder only)</span></span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{challan_link} <span class="text-gray-400 font-sans text-[10px]">(Voucher Link)</span></span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{school_name}</span>
                    <span class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-lg text-purple-700 dark:text-purple-300 font-mono text-xs font-bold shadow-xs">{relation} <span class="text-gray-400 font-sans text-[10px]">(son/daughter/child)</span></span>
                </div>
            </div>

            <!-- Template Editors Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Absent Template --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                Student Absence Notification
                            </label>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 rounded">Attendance</span>
                        </div>
                        <textarea wire:model="templateAbsent" rows="7" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-xs focus:border-purple-500 focus:ring-purple-500 text-xs font-mono"></textarea>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2">Dispatched when a student is marked 'Absent' in daily attendance.</p>
                </div>

                {{-- Leave Template --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                Student Leave Notification
                            </label>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded">Attendance</span>
                        </div>
                        <textarea wire:model="templateLeave" rows="7" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-xs focus:border-purple-500 focus:ring-purple-500 text-xs font-mono"></textarea>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2">Dispatched when a student is marked 'Leave' in daily attendance.</p>
                </div>

                {{-- Late Template --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                                Late Arrival Alert
                            </label>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600 bg-sky-50 dark:bg-sky-900/30 px-2 py-0.5 rounded">Attendance</span>
                        </div>
                        <textarea wire:model="templateLate" rows="7" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-xs focus:border-purple-500 focus:ring-purple-500 text-xs font-mono"></textarea>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2">Dispatched when a student arrives late after initial marking.</p>
                </div>

                {{-- Fee Payment Template --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                Fee Payment Confirmation
                            </label>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded">Fee Module</span>
                        </div>
                        <textarea wire:model="templatePayment" rows="7" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-xs focus:border-purple-500 focus:ring-purple-500 text-xs font-mono"></textarea>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2">Dispatched when a payment receipt is generated or sent to parent.</p>
                </div>

                {{-- Fee Voucher Issuance Template --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                Fee Voucher Issuance Notification
                            </label>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded">Fee Module</span>
                        </div>
                        <textarea wire:model="templateIssuance" rows="7" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-xs focus:border-purple-500 focus:ring-purple-500 text-xs font-mono"></textarea>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2">Dispatched when new fee vouchers are generated for students with digital voucher links.</p>
                </div>

                {{-- Fee Reminder Template --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                Fee Reminder & Defaulter Alert
                            </label>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 bg-purple-50 dark:bg-purple-900/30 px-2 py-0.5 rounded">Fee Module</span>
                        </div>
                        <textarea wire:model="templateReminder" rows="7" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-xs focus:border-purple-500 focus:ring-purple-500 text-xs font-mono"></textarea>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2">Dispatched for single and bulk fee reminders to defaulters with digital voucher links.</p>
                </div>
            </div>

            <!-- Save Action Bar -->
            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="saveTemplates" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-all shadow-md inline-flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveTemplates" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save All Message Templates
                    </span>
                    <span wire:loading wire:target="saveTemplates" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Saving Templates...
                    </span>
                </button>
            </div>
        </div>
    @endif
</div>
