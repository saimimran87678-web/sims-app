<div class="space-y-6 max-w-6xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">WhatsApp Setup & Queue Manager</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Connect WhatsApp and manage automated parent notifications queue</p>
        </div>
        <a href="#queue-manager" class="px-4 py-2 bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800 rounded-xl transition-colors font-semibold text-sm inline-flex items-center gap-2 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
            Jump to Message Queue
        </a>
    </div>

    {{-- Status Card --}}
    <div class="glass-card p-6 sm:p-8 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="text-center space-y-6">
            
            {{-- Connection Status --}}
            @if($isConnected)
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full">
                    <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="font-bold text-lg">Connected & Active</span>
                </div>
                <p class="text-gray-600 dark:text-gray-300">WhatsApp web service is connected and ready to dispatch messages.</p>
                <p class="text-sm text-gray-400">Parent fee reminders and attendance notifications will be sent automatically via queue processor.</p>
                
                <div class="mt-4">
                     <button 
                        wire:click="logout"
                        wire:confirm="Are you sure you want to disconnect WhatsApp? You will need to scan the QR code again."
                        class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-300 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 mx-auto"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" x2="9" y1="12" y2="12"></line></svg>
                        Disconnect / Log Out
                    </button>
                </div>
            @elseif($qrData)
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-yellow-100 text-yellow-700 rounded-full">
                    <span class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></span>
                    <span class="font-bold text-lg">Scan QR Code</span>
                </div>
                
                <div class="bg-white p-4 rounded-xl inline-block shadow-lg">
                    <img src="{{ $qrData }}" alt="WhatsApp QR Code" class="w-64 h-64" />
                </div>
                
                <div class="space-y-2">
                    <p class="text-gray-700 font-medium">Steps to connect:</p>
                    <ol class="text-sm text-gray-500 text-left max-w-xs mx-auto space-y-1">
                        <li>1. Open WhatsApp on your phone</li>
                        <li>2. Tap Menu (⋮) → Linked Devices</li>
                        <li>3. Tap "Link a Device"</li>
                        <li>4. Scan this QR code</li>
                    </ol>
                </div>
            @elseif($errorMessage)
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-red-100 text-red-700 rounded-full">
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                    <span class="font-bold text-lg">Error</span>
                </div>
                <p class="text-red-600 font-semibold">{{ $errorMessage }}</p>
                <p class="text-sm text-gray-500">Make sure the WhatsApp node service is running in your server environment.</p>
            @else
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gray-100 text-gray-700 rounded-full">
                    <span class="w-3 h-3 bg-gray-400 rounded-full animate-pulse"></span>
                    <span class="font-bold text-lg">Initializing Service...</span>
                </div>
                <p class="text-gray-500">Please wait while WhatsApp client checks status...</p>
            @endif

            {{-- Refresh Button --}}
            <div class="pt-2">
                <button 
                    wire:click="refreshStatus"
                    wire:loading.attr="disabled"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm transition-colors inline-flex items-center gap-2 shadow-sm"
                >
                    <span wire:loading.remove wire:target="refreshStatus">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </span>
                    <span wire:loading wire:target="refreshStatus">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    Refresh WhatsApp Status
                </button>
            </div>
        </div>
    </div>

    {{-- Message Queue Manager --}}
    <div id="queue-manager" class="glass-card p-6 sm:p-7 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm scroll-mt-6" wire:poll.10s>
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
            <div>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">WhatsApp Message Queue Manager</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Track queued student fee reminders and attendance notifications</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40 w-fit">
                Context: {{ \App\Models\AcademicSession::find(\App\Models\AcademicSession::getActiveSessionId())->name ?? 'Active Session' }} 
                ({{ ucfirst(session('selected_shift_type', 'morning')) }} Shift)
            </span>
        </div>
        
        {{-- Queue Auto-Send Settings Panel --}}
        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-600 mb-6">
            <h4 class="font-bold text-gray-800 dark:text-gray-200 text-sm mb-3">Auto-Send Configuration</h4>
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
                    @if (session()->has('message'))
                        <span class="text-xs font-bold text-green-600 flex items-center gap-1" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition.duration.500ms>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            {{ session('message') }}
                        </span>
                    @endif
                    @if (session()->has('error'))
                        <span class="text-xs font-bold text-red-600 flex items-center gap-1" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition.duration.500ms>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ session('error') }}
                        </span>
                    @endif
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

        {{-- Queue Table with Detailed Student, Class, Queued Time and Sent Time info --}}
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
                                    <div class="text-[11px] font-semibold text-red-500 dark:text-red-400 block mt-1 truncate" title="{{ $msg->error_message }}">
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
</div>
