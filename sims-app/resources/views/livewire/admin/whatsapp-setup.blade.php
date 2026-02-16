<div class="space-y-6 max-w-2xl mx-auto">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">WhatsApp Setup</h1>
            <p class="text-gray-500">Connect WhatsApp for automated parent notifications</p>
        </div>
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
</div>
