<div class="space-y-6 max-w-4xl mx-auto">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <a href="{{ route('admin.whatsapp-setup') }}" class="text-gray-400 hover:text-purple-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                WhatsApp Message Templates
            </h1>
            <p class="text-gray-500 mt-1">Customize the messages sent to parents. Use the variables below to insert dynamic data.</p>
        </div>
    </div>

    <div class="glass-card p-6 rounded-2xl border border-gray-100 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Legend --}}
            <div class="md:col-span-3 bg-gray-50 p-4 rounded-xl border border-gray-200 text-sm">
                <span class="font-bold text-gray-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Available Variables:
                </span>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-300 rounded text-purple-700 font-mono text-xs shadow-sm">{student_name}</span>
                    <span class="px-2 py-1 bg-white border border-gray-300 rounded text-purple-700 font-mono text-xs shadow-sm">{roll_no}</span>
                    <span class="px-2 py-1 bg-white border border-gray-300 rounded text-purple-700 font-mono text-xs shadow-sm">{date}</span>
                    <span class="px-2 py-1 bg-white border border-gray-300 rounded text-purple-700 font-mono text-xs shadow-sm">{time} <span class="text-gray-400">(Late only)</span></span>
                    <span class="px-2 py-1 bg-white border border-gray-300 rounded text-purple-700 font-mono text-xs shadow-sm">{school_name}</span>
                    <span class="px-2 py-1 bg-white border border-gray-300 rounded text-purple-700 font-mono text-xs shadow-sm">{relation} <span class="text-gray-400">(son/daughter/child)</span></span>
                </div>
            </div>

            {{-- Absent Template --}}
            <div class="md:col-span-3 lg:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-2">Absent Message</label>
                <textarea wire:model="templateAbsent" rows="10" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"></textarea>
                <p class="text-xs text-gray-400 mt-2">Sent when a student is marked as 'A'.</p>
            </div>

            {{-- Leave Template --}}
            <div class="md:col-span-3 lg:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-2">Leave Message</label>
                <textarea wire:model="templateLeave" rows="10" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"></textarea>
                <p class="text-xs text-gray-400 mt-2">Sent when a student is marked as 'L'.</p>
            </div>

            {{-- Late Template --}}
            <div class="md:col-span-3 lg:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-2">Late Message</label>
                <textarea wire:model="templateLate" rows="10" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"></textarea>
                <p class="text-xs text-gray-400 mt-2">Sent for late arrivals (if applicable).</p>
            </div>

            <div class="md:col-span-3 flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                @if (session()->has('message'))
                    <span class="text-sm font-bold text-green-600 flex items-center gap-1" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition.duration.500ms>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ session('message') }}
                    </span>
                @else
                    <span></span>
                @endif
                
                <button wire:click="saveSettings" class="px-6 py-2.5 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors text-sm font-bold shadow-md inline-flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveSettings">Save Templates</span>
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
</div>
