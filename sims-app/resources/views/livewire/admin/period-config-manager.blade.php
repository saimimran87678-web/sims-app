<div class="space-y-6 max-w-4xl mx-auto">
    <div class="flex justify-between items-center">
        <div class="flex items-start gap-4">
            <x-schedule-menu />
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Period Configuration</h1>
                <p class="text-gray-500">Configure school period times</p>
            </div>
        </div>
        <button 
            wire:click="openModal"
            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium flex items-center gap-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Period
        </button>
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700">
            {{ session('message') }}
        </div>
    @endif

    <div class="glass-card rounded-2xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($periods as $period)
                    <tr class="hover:bg-gray-50 {{ $period->is_break ? 'bg-yellow-50' : ($period->is_assembly ? 'bg-purple-50' : '') }}">
                        <td class="px-6 py-4 text-sm font-bold text-gray-700">{{ $period->period_no }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $period->label }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($period->is_assembly)
                                <span class="px-2 py-1 text-xs font-semibold text-purple-700 bg-purple-100 rounded-lg">Assembly</span>
                            @elseif($period->is_break)
                                <span class="px-2 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-lg">Break</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-lg">Class</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="openModal({{ $period->id }})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</button>
                            <button wire:click="delete({{ $period->id }})" wire:confirm="Delete this period?" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No periods configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                {{ $editingId ? 'Edit Period' : 'Add Period' }}
            </h2>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period #</label>
                        <input type="number" wire:model="period_no" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" min="1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                        <input type="text" wire:model="label" placeholder="Period 1" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                        <input type="time" wire:model="start_time" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                        <input type="time" wire:model="end_time" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_break" id="is_break" class="w-4 h-4 text-yellow-500 border-gray-300 rounded focus:ring-yellow-500" />
                        <label for="is_break" class="text-sm font-medium text-gray-700">Break</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_assembly" id="is_assembly" class="w-4 h-4 text-purple-500 border-gray-300 rounded focus:ring-purple-500" />
                        <label for="is_assembly" class="text-sm font-medium text-gray-700">Assembly</label>
                    </div>
                </div>
                <p class="text-xs text-gray-500">Use Period #0 for Assembly before first period.</p>

                @error('period_no') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="save" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium">
                    {{ $editingId ? 'Update' : 'Add' }}
                </button>
                <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-medium">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
