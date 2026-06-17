<div class="space-y-6 max-w-2xl mx-auto">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">WhatsApp Setup</h1>
            <p class="text-gray-500">Connect WhatsApp for automated parent notifications</p>
        </div>
        <a href="#queue-manager" class="px-4 py-2 bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 rounded-xl transition-colors font-medium inline-flex items-center gap-2 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
            Check Message Queue
        </a>
    </div>

    {{-- Status Card --}}
    <div class="glass-card p-8 rounded-2xl">
        <div class="text-center space-y-6">
            
            {{-- Connection Status --}}
            @if($isConnected)
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-green-100 text-green-700 rounded-full">
                    <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="font-bold text-lg">Connected</span>
                </div>
                <p class="text-gray-600">WhatsApp is connected and ready to send messages.</p>
                <p class="text-sm text-gray-400">Parent notifications will be sent automatically when you save attendance.</p>
                
                <div class="mt-4">
                     <button 
                        wire:click="logout"
                        wire:confirm="Are you sure you want to disconnect WhatsApp? You will need to scan the QR code again."
                        class="px-4 py-2 bg-red-100 text-red-700 hover:bg-red-200 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 mx-auto"
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
                <p class="text-red-600">{{ $errorMessage }}</p>
                <p class="text-sm text-gray-500">Make sure the WhatsApp service is running (npm start in whatsapp-service folder).</p>
            @else
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gray-100 text-gray-700 rounded-full">
                    <span class="w-3 h-3 bg-gray-400 rounded-full animate-pulse"></span>
                    <span class="font-bold text-lg">Initializing...</span>
                </div>
                <p class="text-gray-500">Please wait while WhatsApp client starts...</p>
            @endif

            {{-- Refresh Button --}}
            <div class="pt-4">
                <button 
                    wire:click="refreshStatus"
                    wire:loading.attr="disabled"
                    class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium inline-flex items-center gap-2"
                >
                    <span wire:loading.remove wire:target="refreshStatus">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </span>
                    <span wire:loading wire:target="refreshStatus">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    Refresh Status
                </button>
            </div>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="glass-card p-6 rounded-2xl bg-blue-50 border border-blue-100">
        <h3 class="font-bold text-blue-800 mb-2 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            How it works
        </h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Once connected, WhatsApp messages are sent automatically when you save attendance.</li>
            <li>• Parents receive a notification if their child is marked Absent or Leave.</li>
            <li>• The phone used for scanning must stay connected to the internet.</li>
            <li>• You may need to re-scan the QR code periodically (every ~2 weeks).</li>
        </ul>
    </div>

    {{-- Message Queue Manager --}}
    <div id="queue-manager" class="glass-card p-6 rounded-2xl border border-gray-100 mt-6 scroll-mt-6" wire:poll.10s>
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Message Queue Manager</h3>
        </div>
        
        {{-- Queue Settings --}}
        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 mb-6">
            <h4 class="font-semibold text-gray-700 mb-3">Auto-Send Settings</h4>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Service Status</label>
                    <div class="flex items-center h-10" x-data="{ enabled: @entangle('autoSendEnabled') }">
                        <label class="relative inline-flex items-center cursor-pointer" title="Enable to start processing messages">
                            <input type="checkbox" x-model="enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700" x-text="enabled ? 'Enabled' : 'Disabled'">{{ $autoSendEnabled ? 'Enabled' : 'Disabled' }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Start Time</label>
                    <input type="time" wire:model="autoSendStart" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">End Time</label>
                    <input type="time" wire:model="autoSendEnd" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Delay (Secs)</label>
                    <input type="number" min="3" max="60" wire:model="queueDelay" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1 text-red-600">Force Send Now</label>
                    <div class="flex items-center h-10" x-data="{ force: @entangle('forceSendNow') }">
                        <label class="relative inline-flex items-center cursor-pointer" title="Bypass Start/End time and send immediately">
                            <input type="checkbox" x-model="force" class="sr-only peer">
                            <div class="w-11 h-6 bg-red-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-red-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                            <span class="ml-3 text-sm font-bold text-red-600" x-text="force ? 'Active' : 'Off'">{{ $forceSendNow ? 'Active' : 'Off' }}</span>
                        </label>
                    </div>
                </div>
                <div class="md:col-span-5 flex justify-end items-center gap-3">
                    @if (session()->has('message'))
                        <span class="text-sm font-bold text-green-600 flex items-center gap-1" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition.duration.500ms>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            {{ session('message') }}
                        </span>
                    @endif
                    <button wire:click="saveSettings" class="px-5 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-bold shadow-sm inline-flex items-center gap-2">
                        <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                        <span wire:loading wire:target="saveSettings" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Queue Table --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message Preview</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($queue as $msg)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $msg->phone }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <span class="truncate block max-w-xs" title="{{ $msg->message }}">{{ \Illuminate\Support\Str::limit($msg->message, 40) }}</span>
                                @if($msg->error_message)
                                    <span class="text-xs text-red-500 block mt-1" title="{{ $msg->error_message }}">Error: {{ \Illuminate\Support\Str::limit($msg->error_message, 40) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($msg->status === 'sent')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">Sent</span>
                                @elseif($msg->status === 'failed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">Failed</span>
                                @elseif($msg->status === 'paused')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">Paused</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @if(in_array($msg->status, ['pending', 'paused', 'failed']))
                                        <button wire:click="toggleMessageStatus({{ $msg->id }})" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="{{ $msg->status === 'paused' ? 'Play' : 'Pause' }}">
                                            @if($msg->status === 'paused')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            @endif
                                        </button>
                                        <button wire:click="sendManual({{ $msg->id }})" class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Send Now">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                        </button>
                                    @endif
                                    <button wire:click="deleteMessage({{ $msg->id }})" onclick="return confirm('Delete this message?') || event.stopImmediatePropagation()" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">The queue is completely empty.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($queue->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $queue->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
