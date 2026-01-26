<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="flex items-center gap-4">
             <a href="{{ route('admin.schedule') }}" class="p-2 text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors" title="Back to Schedule">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Period Configuration</h1>
                <p class="text-gray-500 text-sm">Configure school period times and breaks</p>
            </div>
        </div>
        <button 
            wire:click="openModal"
            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2 shadow-sm"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Period
        </button>
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700 font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="glass-card rounded-2xl overflow-hidden shadow-sm border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase w-20">#</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Label</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Standard (Mon-Thu, Sat)</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Friday (Override)</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($groupedPeriods as $p)
                    <tr class="hover:bg-gray-50/50 group transition {{ $p['is_break'] ? 'bg-yellow-50/30' : ($p['is_assembly'] ? 'bg-purple-50/30' : '') }}">
                        <td class="px-6 py-4 text-sm font-bold text-gray-700">{{ $p['period_no'] }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $p['label'] }}
                            @if($p['is_assembly'])
                                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold text-purple-700 bg-purple-100 rounded">ASSEMBLY</span>
                            @elseif($p['is_break'])
                                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold text-yellow-700 bg-yellow-100 rounded">BREAK</span>
                            @endif
                        </td>
                        
                        {{-- Standard Time --}}
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if($p['standard'])
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-blue-400"></div>
                                    {{ \Carbon\Carbon::parse($p['standard']->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($p['standard']->end_time)->format('h:i A') }}
                                </div>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        
                        {{-- Friday Time --}}
                        <td class="px-6 py-4 text-sm">
                            @if($p['friday'])
                                <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 px-3 py-1 rounded-lg border border-green-100">
                                    <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                                    {{ \Carbon\Carbon::parse($p['friday']->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($p['friday']->end_time)->format('h:i A') }}
                                </div>
                            @else
                                <span class="text-gray-400 text-xs italic">Same as Standard</span>
                            @endif
                        </td>
                        
                        <td class="px-6 py-4 text-right space-x-1 opacity-60 group-hover:opacity-100 transition-opacity">
                            <button wire:click="openModal({{ $p['period_no'] }})" class="p-1 text-blue-600 hover:bg-blue-50 rounded transition"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                            <button wire:click="delete({{ $p['period_no'] }})" wire:confirm="Delete Period {{ $p['period_no'] }}? This will remove standard and Friday settings." class="p-1 text-red-600 hover:bg-red-50 rounded transition"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 bg-gray-50/50">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>No periods configured for this schedule.</span>
                                @if($selectedTemplateId)
                                    <button wire:click="openModal" class="text-blue-600 hover:underline">Add First Period</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                @if($editingPeriodNo === 'new')
                    Add New Period
                @else
                    Edit Period {{ $p_period_no }}
                @endif
            </h2>

             <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 mb-6">
                <div class="grid grid-cols-4 gap-6">
                     <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Period Number</label>
                        <input type="number" wire:model="p_period_no" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white font-bold text-gray-700" min="1" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Label</label>
                        <input type="text" wire:model="p_label" placeholder="e.g. Period 1" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white" />
                    </div>
                    <div class="col-span-1 flex flex-col justify-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" wire:model="p_is_break" class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-500 w-5 h-5" />
                            <span class="text-sm font-medium text-gray-600 group-hover:text-yellow-600">Is Break</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" wire:model="p_is_assembly" class="rounded border-gray-300 text-purple-500 focus:ring-purple-500 w-5 h-5" />
                            <span class="text-sm font-medium text-gray-600 group-hover:text-purple-600">Is Assembly</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Standard Column -->
                <div class="relative">
                     <div class="absolute -left-3 top-0 bottom-0 w-1 bg-blue-100 rounded-full"></div>
                     <div class="pl-4">
                        <h3 class="font-bold text-gray-800 mb-1">Standard Timings</h3>
                        <div class="flex items-center gap-2 mb-4">
                            <p class="text-xs text-gray-500">Monday - Thursday</p>
                            <span class="text-gray-300">|</span>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:model.live="isSaturdayWorking" class="w-3.5 h-3.5 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="text-xs font-semibold {{ $isSaturdayWorking ? 'text-blue-600' : 'text-gray-400' }}">Include Saturday</span>
                            </label>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-blue-600 mb-1">Start Time</label>
                                <input type="time" wire:model="p_standard_start" class="w-full px-4 py-2 rounded-xl border border-blue-100 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-blue-600 mb-1">End Time</label>
                                <input type="time" wire:model="p_standard_end" class="w-full px-4 py-2 rounded-xl border border-blue-100 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Friday Column -->
                <div class="relative">
                     <div class="absolute -left-3 top-0 bottom-0 w-1 bg-green-100 rounded-full"></div>
                     <div class="pl-4">
                        <h3 class="font-bold text-gray-800 mb-1">Friday Override</h3>
                        <p class="text-xs text-gray-500 mb-4">Custom timings specifically for Friday</p>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-green-600 mb-1">Start Time</label>
                                <input type="time" wire:model="p_friday_start" class="w-full px-4 py-2 rounded-xl border border-green-100 focus:ring-2 focus:ring-green-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-green-600 mb-1">End Time</label>
                                <input type="time" wire:model="p_friday_end" class="w-full px-4 py-2 rounded-xl border border-green-100 focus:ring-2 focus:ring-green-500 outline-none" />
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 italic">Leave empty to use Standard timings on Friday.</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-8 pt-6 border-t border-gray-100">
                <button wire:click="save" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-bold shadow-lg shadow-blue-200 transition-all transform hover:scale-[1.02]">
                    {{ $editingPeriodNo === 'new' ? 'Create Period Configuration' : 'Save Changes' }}
                </button>
                <button wire:click="closeModal" class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 font-medium transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
