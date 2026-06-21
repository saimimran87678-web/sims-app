<div>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Fee Data Entry</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure and save fee structures for students.</p>
            </div>
        </div>

        <!-- Error/Success Messages -->
        @if (session()->has('message'))
            <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800 flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        <div class="max-w-5xl mx-auto pb-12">
            <!-- Main Form Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                
                <!-- Header Section (Inline Selectors) -->
                <div class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Select Class</label>
                            <select wire:model.live="selectedClassId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:text-white transition-colors">
                                <option value="">-- Choose Class --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Target</label>
                            <select wire:model.live="selectedTarget" class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:text-white transition-colors disabled:opacity-50 disabled:bg-gray-100" {{ !$selectedClassId ? 'disabled' : '' }}>
                                <option value="all">« Entire Class »</option>
                                @if($selectedClassId)
                                    @foreach($students as $id => $student)
                                        <option value="{{ $id }}">{{ $student['name'] }} ({{ $student['roll_no'] }})@if($student['is_custom'] ?? false) * [Custom Fee] @endif</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Billing Month</label>
                            <input type="month" wire:model.live="billingMonth" class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:text-white transition-colors">
                        </div>
                    </div>
                </div>

                @if($selectedClassId)
                <!-- Data Entry Section -->
                <div class="p-6">
                    
                    @if($selectedTarget !== 'all' && isset($students[$selectedTarget]))
                    <!-- Student Personal Info Panel -->
                    <div class="mb-6 p-4 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-100 dark:bg-blue-950 p-2.5 rounded-lg text-blue-600 dark:text-blue-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ $students[$selectedTarget]['name'] }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Roll No: {{ $students[$selectedTarget]['roll_no'] }}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-2 text-xs flex-1 md:justify-items-end">
                            <div class="text-left md:text-right">
                                <span class="text-gray-500 dark:text-gray-400">Father's Name</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-200 block mt-0.5">{{ $students[$selectedTarget]['father_name'] }}</span>
                            </div>
                            <div class="text-left md:text-right">
                                <span class="text-gray-500 dark:text-gray-400">Adm. Number</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-200 block mt-0.5">{{ $students[$selectedTarget]['admission_no'] }}</span>
                            </div>
                            <div class="text-left md:text-right">
                                <span class="text-gray-500 dark:text-gray-400">Phone</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-200 block mt-0.5">{{ $students[$selectedTarget]['phone'] }}</span>
                            </div>
                            <div class="text-left md:text-right">
                                <span class="text-gray-500 dark:text-gray-400">Arrears</span>
                                <span class="font-bold text-red-600 dark:text-red-400 block mt-0.5">Rs. {{ number_format($students[$selectedTarget]['arrears']) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Table -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden mb-8 shadow-sm">
                        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-400">
                            <thead class="bg-gray-100 dark:bg-gray-700/80 text-xs uppercase font-bold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-5 py-4">Fee Description</th>
                                    <th class="px-5 py-4 w-48 border-l border-gray-200 dark:border-gray-700">Payment Category</th>
                                    <th class="px-5 py-4 w-40 text-right border-l border-gray-200 dark:border-gray-700">Amount (Rs)</th>
                                    <th class="px-4 py-4 w-16 text-center border-l border-gray-200 dark:border-gray-700"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($baseItems as $index => $item)
                                <tr wire:key="item-{{ $item['id'] }}" class="bg-white dark:bg-gray-800 hover:bg-yellow-50/30 dark:hover:bg-gray-700/30 transition-colors group">
                                    <td class="p-2">
                                        <select wire:model.live="baseItems.{{ $index }}.name" wire:change="handleFeeHeadChange({{ $index }}, $event.target.value)" class="w-full border-0 bg-transparent focus:ring-0 p-2 text-gray-900 dark:text-gray-100 font-medium focus:outline-none">
                                            <option value="">-- Select Payment Head --</option>
                                            @foreach($feeHeads as $name)
                                                <option value="{{ $name }}">{{ $name }}</option>
                                            @endforeach
                                            <option value="__manage__" class="text-blue-600 dark:text-blue-400 font-bold">+ Add / Manage Heads...</option>
                                        </select>
                                    </td>
                                    <td class="p-2 border-l border-gray-200 dark:border-gray-700">
                                        <select wire:model.live="baseItems.{{ $index }}.category" class="w-full border-0 bg-transparent text-sm focus:ring-0 text-gray-700 dark:text-gray-300">
                                            <option value="monthly">Monthly</option>
                                            <option value="one_time">One-Time</option>
                                        </select>
                                    </td>
                                    <td class="p-2 border-l border-gray-200 dark:border-gray-700">
                                        <input type="number" wire:model.live.debounce.500ms="baseItems.{{ $index }}.amount" placeholder="0" class="w-full border-0 bg-transparent text-right font-bold focus:ring-0 p-2 text-gray-900 dark:text-gray-100">
                                    </td>
                                    <td class="p-2 text-center border-l border-gray-200 dark:border-gray-700">
                                        <button wire:click="removeRow({{ $index }})" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-2 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Remove Item">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-3 border-t border-gray-200 dark:border-gray-700">
                            <button wire:click="addRow" class="text-sm font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1.5 px-2 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors uppercase tracking-wide">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                                Add Fee Item
                            </button>
                        </div>
                    </div>

                    <!-- Summary & Totals -->
                    <div class="flex flex-col items-end space-y-4 text-sm bg-gray-50 dark:bg-gray-800/80 p-6 rounded-xl border border-gray-100 dark:border-gray-700 w-full md:w-96 ml-auto shadow-sm">
                        
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center w-full text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Subtotal:</span>
                            <span class="font-bold text-gray-900 dark:text-white">Rs. {{ number_format($this->subtotal, 2) }}</span>
                        </div>

                        <!-- Discount -->
                        <div class="flex flex-col gap-2 w-full pt-2 border-t border-gray-100 dark:border-gray-700/50">
                            <div class="flex justify-between items-center w-full">
                                <span class="flex items-center gap-2 text-yellow-700 dark:text-yellow-500 font-bold">
                                    Discount
                                    @if($selectedTarget !== 'all')
                                        <span class="text-[9px] bg-yellow-200 dark:bg-yellow-900/50 px-1.5 py-0.5 rounded-full text-yellow-800 dark:text-yellow-400 uppercase tracking-widest">Specific</span>
                                    @else
                                        <span class="text-[9px] bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded-full text-gray-600 dark:text-gray-400 uppercase tracking-widest">Global</span>
                                    @endif
                                </span>
                                <div class="flex items-center">
                                    <span class="text-gray-400 mr-2">- Rs.</span>
                                    <input type="number" wire:model.live.debounce.500ms="currentDiscountInput" class="w-24 border-gray-300 dark:border-gray-600 rounded-lg text-right font-bold text-yellow-700 dark:text-yellow-500 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-900 shadow-sm px-2 py-1.5" placeholder="0">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 w-full mt-1">
                                <div>
                                    <input type="text" wire:model.live.debounce.500ms="currentDiscountDescInput" placeholder="Reason (e.g. Sibling)" class="w-full text-xs border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-900 shadow-sm px-2 py-1.5">
                                </div>
                                <div>
                                    <select wire:model.live="currentDiscountCategoryInput" class="w-full text-xs border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-900 shadow-sm px-2 py-1.5">
                                        <option value="monthly">Monthly Cycle</option>
                                        <option value="one_time">One-Time Only</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Arrears -->
                        <div class="flex justify-between items-center w-full text-red-600 dark:text-red-400">
                            <span class="font-medium">
                                Previous Arrears
                                @if($selectedTarget === 'all')
                                    <span class="text-[10px] ml-1 opacity-70 italic font-normal">(Auto)</span>
                                @endif
                            </span>
                            <span class="font-bold">
                                + Rs. 
                                @if($selectedTarget === 'all')
                                    <span class="opacity-70 italic font-normal">Var.</span>
                                @else
                                    {{ number_format($students[$selectedTarget]['arrears'] ?? 0, 2) }}
                                @endif
                            </span>
                        </div>

                        <!-- Grand Total -->
                        <div class="flex justify-between items-center w-full pt-4 border-t-2 border-gray-200 dark:border-gray-600 mt-2">
                            <span class="text-base font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wide">Total Payable</span>
                            <span class="text-2xl font-black text-blue-600 dark:text-blue-400">
                                <span class="text-lg text-gray-400 mr-1 font-normal">Rs.</span>
                                @if($selectedTarget === 'all')
                                    {{ number_format(max(0, $this->subtotal - $this->currentDiscount), 2) }} <span class="text-xs font-normal text-gray-500 ml-1">+ Arr.</span>
                                @else
                                    {{ number_format(max(0, $this->subtotal + ($students[$selectedTarget]['arrears'] ?? 0) - $this->currentDiscount), 2) }}
                                @endif
                            </span>
                        </div>

                    </div>

                    <!-- Action Button -->
                    <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            @if($selectedTarget === 'all')
                                <label class="inline-flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" wire:model.live="includeCustom" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 mr-2 dark:bg-gray-900 transition-colors">
                                    Include Custom Students (overwrite custom fees)
                                </label>
                            @endif
                        </div>
                        <div class="flex justify-end">
                            <button wire:click="generateInvoices" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg shadow-blue-600/20 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 disabled:hover:scale-100" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="generateInvoices" class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                    Save Fee Data
                                </span>
                                <span wire:loading wire:target="generateInvoices" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                @else
                <!-- Empty State -->
                <div class="p-16 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/50">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-full shadow-sm mb-4">
                        <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">No Class Selected</h3>
                    <p class="text-sm font-medium text-center max-w-md">Select a class from the top menu to start configuring the fee data.</p>
                </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Manage Payment Heads Modal -->
    @if($showManageHeadsModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-xl max-w-lg w-full p-6 shadow-xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in duration-200 flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="flex justify-between items-center pb-4 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Payment Heads</h3>
                <button wire:click="$set('showManageHeadsModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Content (Scrollable Container) -->
            <div class="overflow-y-auto py-4 flex-1 space-y-6 pr-1">
                <!-- Add New Form -->
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-2.5">Create New Head</h4>
                    <form wire:submit.prevent="createFeeHead" class="flex gap-2 items-end">
                        <div class="flex-1">
                            <input type="text" wire:model="newFeeHeadName" placeholder="E.g. Computer Lab Fee" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-950 dark:text-white px-3 py-2" required>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2 rounded-lg shadow-sm transition-all active:scale-95 flex items-center gap-1.5 h-[38px] flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            Add
                        </button>
                    </form>
                    @error('newFeeHeadName') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Existing List -->
                <div>
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Existing Payment Heads</h4>
                    @if(empty($feeHeadList))
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic py-4 text-center">No payment heads configured yet.</p>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-800 border border-gray-100 dark:border-gray-800 rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                            @foreach($feeHeadList as $head)
                                <div class="p-3.5 flex items-center justify-between gap-4 group/item hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                    @if($editingFeeHeadId === $head['id'])
                                        <!-- Inline Edit Form -->
                                        <div class="flex-1 flex gap-2 items-center">
                                            <input type="text" wire:model="editingFeeHeadName" class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-950 dark:text-white px-2.5 py-1.5" required>
                                            <button wire:click="updateFeeHead" class="text-xs font-bold text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 px-2 py-1.5 bg-green-50 dark:bg-green-950/30 rounded border border-green-200 dark:border-green-800">
                                                Save
                                            </button>
                                            <button wire:click="$set('editingFeeHeadId', null)" class="text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                                                Cancel
                                            </button>
                                        </div>
                                    @else
                                        <!-- Normal List Item View -->
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $head['name'] }}</span>
                                        <div class="flex items-center gap-1.5">
                                            <button wire:click="editFeeHead({{ $head['id'] }})" class="p-1.5 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Rename Head">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <button wire:click="deleteFeeHead({{ $head['id'] }})" onclick="confirm('Are you sure you want to delete this payment head? It might be in use.') || event.stopImmediatePropagation()" class="p-1.5 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Delete Head">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end flex-shrink-0">
                <button wire:click="$set('showManageHeadsModal', false)" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-sm font-bold text-gray-800 dark:text-white rounded-lg transition-colors">
                    Close
                </button>
            </div>

        </div>
    </div>
    @endif
</div>

