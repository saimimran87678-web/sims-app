<div>
    <div class="p-4 sm:p-6 lg:p-8">
        <!-- Page Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-750 pb-5">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Fee Voucher Management</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">Configure templates, view active vouchers, and manage payment receipts.</p>
            </div>
            
            <!-- Tab Shifter -->
            <div class="flex bg-gray-100 dark:bg-gray-800/80 p-1 rounded-xl border border-gray-200 dark:border-gray-700/60 self-start md:self-auto shrink-0 shadow-inner">
                <button 
                    wire:click="$set('activeTab', 'setup')" 
                    class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all flex items-center gap-2 {{ $activeTab === 'setup' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Voucher Setup
                </button>
                <button 
                    wire:click="$set('activeTab', 'vouchers')" 
                    class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all flex items-center gap-2 {{ $activeTab === 'vouchers' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    View Vouchers
                </button>
                <button 
                    wire:click="$set('activeTab', 'receipts')" 
                    class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all flex items-center gap-2 {{ $activeTab === 'receipts' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2-2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    View Receipts
                </button>
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
            @if($activeTab === 'setup')
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

                        <!-- Assign Subjects Button -->
                        <div class="flex-shrink-0 pt-2 md:pt-0">
                            <button wire:click="openSubjectEnrollmentModal"
                                class="inline-flex items-center gap-2 px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-lg shadow shadow-violet-600/25 transition-all hover:scale-[1.02] active:scale-95"
                                title="Manage which subjects this student is enrolled in">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                Assign Subjects
                            </button>
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
            @elseif($activeTab === 'vouchers')
            <!-- View Vouchers Tab Content -->
            <div class="space-y-6 animate-in fade-in duration-300">
                <!-- Filters Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-250 dark:border-gray-700 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Select Class -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Select Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="viewClassId" class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-lg text-gray-750 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900 shadow-sm px-3 py-2.5">
                                <option value="">-- Choose Class --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Select Student -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Select Student</label>
                            <select wire:model.live="viewStudentId" class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-lg text-gray-750 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900 shadow-sm px-3 py-2.5" {{ !$viewClassId ? 'disabled' : '' }}>
                                <option value="all">All Students</option>
                                @foreach($viewStudents as $stdId => $stdName)
                                    <option value="{{ $stdId }}">{{ $stdName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Select Billing Month -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Billing Month</label>
                            <input type="month" wire:model.live="viewMonth" class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-lg text-gray-750 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900 shadow-sm px-3 py-2.5">
                        </div>
                    </div>
                </div>

                <!-- Vouchers Table -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-250 dark:border-gray-700 overflow-hidden">
                    @if(!$viewClassId)
                        <!-- Empty State: No Class Selected -->
                        <div class="p-16 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-full shadow-sm mb-4">
                                <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">No Class Selected</h3>
                            <p class="text-sm font-medium text-center max-w-md">Please choose a class from the filter options to load students' vouchers.</p>
                        </div>
                    @elseif($this->vouchers->isEmpty())
                        <!-- Empty State: No Vouchers Found -->
                        <div class="p-16 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-full shadow-sm mb-4">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">No Vouchers Found</h3>
                            <p class="text-sm font-medium text-center max-w-md">We couldn't find any generated vouchers for the selected period.</p>
                        </div>
                    @else
                        <!-- Vouchers Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-800">
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Student Details</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Billing Month</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Due Date</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Amounts</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($this->vouchers as $voucher)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                            <td class="p-4">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $voucher->student->name }}</span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Roll: {{ $voucher->student->roll_number ?? 'N/A' }} | Adm: {{ $voucher->student->admission_number ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="p-4 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                                {{ \Carbon\Carbon::parse($voucher->period . '-01')->format('F Y') }}
                                            </td>
                                            <td class="p-4 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                                {{ $voucher->due_date->format('d M, Y') }}
                                            </td>
                                            <td class="p-4">
                                                <div class="flex flex-col text-xs font-semibold">
                                                    <span class="text-gray-600 dark:text-gray-400">Total: Rs. {{ number_format($voucher->total_amount, 2) }}</span>
                                                    <span class="text-green-600 dark:text-green-400">Paid: Rs. {{ number_format($voucher->paid_amount, 2) }}</span>
                                                    <span class="text-red-600 dark:text-red-400 font-bold">Balance: Rs. {{ number_format($voucher->balance, 2) }}</span>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                @if($voucher->status === 'paid')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Paid</span>
                                                @elseif($voucher->status === 'partial')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Partial</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">Unpaid</span>
                                                @endif
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="flex justify-end items-center gap-2">
                                                    <a href="{{ route('admin.fee.invoice.download', $voucher->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:hover:bg-blue-900/45 dark:text-blue-400 text-xs font-bold rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                        Print/PDF
                                                    </a>
                                                    <button wire:click="sendVoucherWhatsApp({{ $voucher->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 dark:bg-green-900/20 dark:hover:bg-green-900/45 dark:text-green-400 text-xs font-bold rounded-lg transition-colors" wire:loading.attr="disabled" wire:target="sendVoucherWhatsApp({{ $voucher->id }})">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.003 5.324 5.328 0 11.859 0c3.166.001 6.141 1.233 8.377 3.469 2.235 2.235 3.466 5.21 3.466 8.377-.003 6.534-5.328 11.859-11.859 11.859-1.996-.001-3.957-.503-5.707-1.46L0 24zm5.835-4.265c1.62.962 3.218 1.488 4.931 1.49 5.373 0 9.742-4.369 9.745-9.743 0-2.602-1.012-5.05-2.849-6.888C15.83 2.756 13.38 1.745 10.781 1.745c-5.372 0-9.742 4.37-9.745 9.743-.001 1.83.491 3.58 1.42 5.176l-.991 3.616 3.7-.971zm13.14-8.122c-.27-.135-1.597-.787-1.845-.877-.247-.09-.427-.135-.607.135-.18.27-.697.877-.855 1.057-.158.18-.315.202-.585.067-.27-.135-1.139-.42-2.17-1.34-.801-.715-1.343-1.6-1.5-1.871-.158-.27-.017-.417.118-.552.122-.122.27-.315.405-.472.135-.158.18-.27.27-.45.09-.18.045-.337-.022-.472-.067-.135-.607-1.462-.832-2.002-.22-.53-.442-.457-.607-.466-.158-.008-.338-.01-.518-.01-.18 0-.472.067-.72.338-.247.27-.945.922-.945 2.25s.967 2.61 1.102 2.79c.135.18 1.902 2.904 4.609 4.073.644.279 1.147.445 1.54.57.647.206 1.236.177 1.701.108.518-.077 1.598-.652 1.823-1.282.225-.63.225-1.17.157-1.282-.068-.113-.248-.18-.518-.315z"/></svg>
                                                        WhatsApp
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @elseif($activeTab === 'receipts')
            <!-- View Receipts Tab Content -->
            <div class="space-y-6 animate-in fade-in duration-300">
                <!-- Filters Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-250 dark:border-gray-700 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Select Class -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Select Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="receiptClassId" class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-lg text-gray-750 dark:text-gray-300 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-900 shadow-sm px-3 py-2.5">
                                <option value="">-- Choose Class --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Select Student -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Select Student</label>
                            <select wire:model.live="receiptStudentId" class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-lg text-gray-750 dark:text-gray-300 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-900 shadow-sm px-3 py-2.5" {{ !$receiptClassId ? 'disabled' : '' }}>
                                <option value="all">All Students</option>
                                @foreach($receiptStudents as $stdId => $stdName)
                                    <option value="{{ $stdId }}">{{ $stdName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Select Billing Month -->
                        <div>
                            <label class="block text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Billing Month</label>
                            <input type="month" wire:model.live="receiptMonth" class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-lg text-gray-750 dark:text-gray-300 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-900 shadow-sm px-3 py-2.5">
                        </div>
                    </div>
                </div>

                <!-- Receipts Table -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-250 dark:border-gray-700 overflow-hidden">
                    @if(!$receiptClassId)
                        <!-- Empty State: No Class Selected -->
                        <div class="p-16 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-full shadow-sm mb-4">
                                <svg class="w-12 h-12 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2-2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">No Class Selected</h3>
                            <p class="text-sm font-medium text-center max-w-md">Please choose a class from the filter options to load payment receipts.</p>
                        </div>
                    @elseif($this->receipts->isEmpty())
                        <!-- Empty State: No Receipts Found -->
                        <div class="p-16 flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-full shadow-sm mb-4">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">No Receipts Found</h3>
                            <p class="text-sm font-medium text-center max-w-md">We couldn't find any recorded payments for the selected period.</p>
                        </div>
                    @else
                        <!-- Receipts Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-150 dark:border-gray-800">
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Student Details</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Receipt No / Date</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Payment Method</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Amount Paid</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Remarks / Notes</th>
                                        <th class="p-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($this->receipts as $receipt)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                            <td class="p-4">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $receipt->student->name }}</span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Class: {{ $receipt->record->class->name }} | Roll: {{ $receipt->student->roll_number ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">REC-{{ str_pad($receipt->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $receipt->payment_date->format('d M, Y') }}</span>
                                                </div>
                                            </td>
                                            <td class="p-4 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                                <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-xs font-bold uppercase">{{ $receipt->payment_method }}</span>
                                            </td>
                                            <td class="p-4 text-sm text-emerald-600 dark:text-emerald-400 font-extrabold">
                                                Rs. {{ number_format($receipt->amount_paid, 2) }}
                                            </td>
                                            <td class="p-4 text-xs text-gray-600 dark:text-gray-400 font-medium max-w-xs truncate">
                                                {{ $receipt->notes ?: '-' }}
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="flex justify-end items-center gap-2">
                                                    <a href="{{ route('admin.fee.receipt.download', $receipt->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:hover:bg-blue-900/45 dark:text-blue-400 text-xs font-bold rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                        Print/PDF
                                                    </a>
                                                    <button wire:click="sendReceiptWhatsApp({{ $receipt->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 dark:bg-green-900/20 dark:hover:bg-green-900/45 dark:text-green-400 text-xs font-bold rounded-lg transition-colors" wire:loading.attr="disabled" wire:target="sendReceiptWhatsApp({{ $receipt->id }})">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.003 5.324 5.328 0 11.859 0c3.166.001 6.141 1.233 8.377 3.469 2.235 2.235 3.466 5.21 3.466 8.377-.003 6.534-5.328 11.859-11.859 11.859-1.996-.001-3.957-.503-5.707-1.46L0 24zm5.835-4.265c1.62.962 3.218 1.488 4.931 1.49 5.373 0 9.742-4.369 9.745-9.743 0-2.602-1.012-5.05-2.849-6.888C15.83 2.756 13.38 1.745 10.781 1.745c-5.372 0-9.742 4.37-9.745 9.743-.001 1.83.491 3.58 1.42 5.176l-.991 3.616 3.7-.971zm13.14-8.122c-.27-.135-1.597-.787-1.845-.877-.247-.09-.427-.135-.607.135-.18.27-.697.877-.855 1.057-.158.18-.315.202-.585.067-.27-.135-1.139-.42-2.17-1.34-.801-.715-1.343-1.6-1.5-1.871-.158-.27-.017-.417.118-.552.122-.122.27-.315.405-.472.135-.158.18-.27.27-.45.09-.18.045-.337-.022-.472-.067-.135-.607-1.462-.832-2.002-.22-.53-.442-.457-.607-.466-.158-.008-.338-.01-.518-.01-.18 0-.472.067-.72.338-.247.27-.945.922-.945 2.25s.967 2.61 1.102 2.79c.135.18 1.902 2.904 4.609 4.073.644.279 1.147.445 1.54.57.647.206 1.236.177 1.701.108.518-.077 1.598-.652 1.823-1.282.225-.63.225-1.17.157-1.282-.068-.113-.248-.18-.518-.315z"/></svg>
                                                        WhatsApp
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endif
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

    <!-- Subject Enrollment Modal -->
    @if($showSubjectEnrollmentModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showSubjectEnrollmentModal', false)">
        <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full mx-4 shadow-2xl border border-gray-200 dark:border-gray-700 animate-in fade-in zoom-in duration-200 flex flex-col max-h-[80vh]">

            <!-- Modal Header -->
            <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 bg-violet-100 dark:bg-violet-900/40 rounded-lg">
                            <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </span>
                        Assign Subjects
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        @if(isset($students[$selectedTarget]))
                            {{ $students[$selectedTarget]['name'] }} &mdash; Check subjects to restrict enrollment. Unchecked = All subjects.
                        @endif
                    </p>
                </div>
                <button wire:click="$set('showSubjectEnrollmentModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Info Banner -->
            <div class="px-6 pt-4 flex-shrink-0">
                <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-amber-700 dark:text-amber-400">
                        <strong>Default:</strong> If no subjects are checked, the student is enrolled in all class subjects. Check specific subjects to restrict them (e.g. Computer Science group vs. Biology group).
                    </p>
                </div>
            </div>

            <!-- Subjects List -->
            <div class="px-6 py-4 overflow-y-auto flex-1 space-y-1.5">
                @if(empty($classSubjectsList))
                    <div class="flex flex-col items-center justify-center py-10 text-gray-400 dark:text-gray-500">
                        <svg class="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <p class="text-sm font-medium">No subjects found for this class.</p>
                        <p class="text-xs mt-1">Add subjects to this class from the class management section.</p>
                    </div>
                @else
                    @foreach($classSubjectsList as $subject)
                    <label wire:key="sub-{{ $subject['id'] }}" class="flex items-center gap-3 p-3 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 transition-all group">
                        <div class="relative flex-shrink-0">
                            <input
                                type="checkbox"
                                wire:model="studentSubjectsList"
                                value="{{ $subject['id'] }}"
                                class="w-4 h-4 rounded text-violet-600 border-gray-300 dark:border-gray-600 focus:ring-violet-500 dark:bg-gray-700 transition-colors cursor-pointer"
                            >
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-violet-700 dark:group-hover:text-violet-400 transition-colors">
                                {{ $subject['name'] }}
                            </span>
                            @if(!empty($subject['code']))
                            <span class="ml-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $subject['code'] }}</span>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            @if(in_array((string)$subject['id'], $studentSubjectsList))
                                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-100 dark:bg-violet-900/40 px-2 py-0.5 rounded-full">Enrolled</span>
                            @else
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500">&mdash;</span>
                            @endif
                        </div>
                    </label>
                    @endforeach
                @endif
            </div>

            <!-- Quick Actions -->
            @if(!empty($classSubjectsList))
            <div class="px-6 pt-3 border-t border-gray-100 dark:border-gray-700 flex gap-2 flex-shrink-0">
                <button type="button"
                    wire:click="$set('studentSubjectsList', {{ json_encode(collect($classSubjectsList)->pluck('id')->map(fn($id) => (string)$id)->toArray()) }})"
                    class="text-xs font-semibold text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300 px-2 py-1 rounded hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                    Check All
                </button>
                <button type="button"
                    wire:click="$set('studentSubjectsList', [])"
                    class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    Uncheck All (Default)
                </button>
            </div>
            @endif

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3 flex-shrink-0">
                <button wire:click="$set('showSubjectEnrollmentModal', false)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    Cancel
                </button>
                <button wire:click="saveSubjectEnrollments" class="px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-lg shadow shadow-violet-600/20 transition-all hover:scale-[1.02] active:scale-95 flex items-center gap-2" wire:loading.attr="disabled" wire:target="saveSubjectEnrollments">
                    <span wire:loading.remove wire:target="saveSubjectEnrollments">Save Enrollment</span>
                    <span wire:loading wire:target="saveSubjectEnrollments" class="flex items-center gap-1.5">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Saving...
                    </span>
                </button>
            </div>

        </div>
    </div>
    @endif
</div>

