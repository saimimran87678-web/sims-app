<div>
    <!-- Page Mode: Search & Selection -->
    @if($isPage && !$isOpen)
        <div class="max-w-4xl mx-auto py-8 px-4">
            <!-- Breadcrumbs / Title -->
            <div class="mb-8">
                <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                    <span class="p-3 bg-gradient-to-tr from-emerald-500 to-teal-600 rounded-2xl text-white shadow-lg shadow-emerald-500/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </span>
                    Collect Student Fees
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Search student, select unpaid vouchers, and record payment</p>
            </div>

            @if(!$selectedStudentId)
                <!-- Step 1: Search Student -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                    <div class="flex flex-col md:flex-row gap-4 w-full">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Type Student Name, Admission No, or Roll No..." 
                                   style="padding-left: 3.25rem;"
                                   class="block w-full pr-4 py-4 border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-2xl focus:border-emerald-500 focus:ring-emerald-500 focus:bg-white text-base font-medium transition-all shadow-sm">
                        </div>
                        <div class="w-full md:w-64">
                            <select wire:model.live="filter_class" class="block w-full px-4 py-4 border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-2xl focus:border-emerald-500 focus:ring-emerald-500 text-base font-semibold transition-all shadow-sm">
                                <option value="">All Classes</option>
                                @foreach($classes as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full md:w-48">
                            <select wire:model.live="filter_status" class="block w-full px-4 py-4 border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-2xl focus:border-emerald-500 focus:ring-emerald-500 text-base font-semibold transition-all shadow-sm">
                                <option value="active">Active Only</option>
                                <option value="inactive">Inactive Only</option>
                                <option value="">All Statuses</option>
                            </select>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div class="mt-8">
                        @if(!empty(trim($search)) || $filter_class)
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Matching Students</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @forelse($studentsList as $st)
                                    <div class="bg-gray-50 dark:bg-gray-900/50 hover:bg-emerald-50/55 dark:hover:bg-emerald-950/20 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-emerald-200 dark:hover:border-emerald-800 transition-all flex justify-between items-center group">
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors text-base">{{ $st->name }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Class: {{ $st->class->name }} | Admn: {{ $st->admission_no }} | 
                                                <span class="font-bold {{ $st->status === 'active' ? 'text-green-650 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ ucfirst($st->status ?? 'Active') }}
                                                </span>
                                            </p>
                                            @if($st->total_due > 0)
                                                <span class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400">
                                                    Due: Rs. {{ number_format($st->total_due, 2) }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400">
                                                    Fully Paid
                                                </span>
                                            @endif
                                        </div>
                                        <button wire:click="selectStudent({{ $st->id }})" 
                                                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-emerald-500 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-600 rounded-xl font-bold text-sm transition-all shadow-sm">
                                            Select
                                        </button>
                                    </div>
                                @empty
                                    <div class="col-span-2 text-center py-8 text-gray-500">
                                        No student matches the search criteria.
                                    </div>
                                @endforelse
                            </div>
                        @else
                            <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                <p class="text-lg font-medium">Search for a student to collect fee</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Step 2: Show Unpaid Vouchers -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <!-- Selected Student Header -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-6 md:p-8 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400">{{ $selectedStudent->class->name }}</span>
                                <span class="text-xs text-gray-500">Admn: {{ $selectedStudent->admission_no }}</span>
                            </div>
                            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $selectedStudent->name }}</h2>
                        </div>
                        <button wire:click="resetStudentSelection" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-semibold transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back to Search
                        </button>
                    </div>

                    <div class="p-6 md:p-8">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Unpaid Fee Vouchers</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Billing Period</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Amount</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Balance Due</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($unpaidRecords as $rec)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-gray-900 dark:text-white text-base">{{ \Carbon\Carbon::parse($rec->period . '-01')->format('F Y') }}</div>
                                                <div class="text-[11px] text-gray-500 capitalize mt-0.5">{{ str_replace('_', ' ', $rec->cycle) }} cycle</div>
                                            </td>
                                            <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">Rs. {{ number_format($rec->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 text-right font-black text-red-600 dark:text-red-400">Rs. {{ number_format($rec->balance, 2) }}</td>
                                            <td class="px-6 py-4">
                                                @if($rec->period > now()->format('Y-m'))
                                                    <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Upcoming</span>
                                                @elseif($rec->status === 'partial')
                                                    <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">Partially Paid</span>
                                                @else
                                                    <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">Unpaid</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button wire:click="selectRecord({{ $rec->id }})" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm">
                                                    Collect Payment
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                                <svg class="w-12 h-12 mx-auto mb-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <p class="text-base font-semibold text-gray-700 dark:text-gray-300">All bills are fully paid!</p>
                                                <p class="text-xs text-gray-500 mt-1">This student does not have any pending balance.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Payment Dialog (Modal or Full View) -->
    @if($isOpen && $record)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden border border-gray-100 dark:border-gray-750 transform scale-100 transition-all duration-300">
                <!-- Modal Header -->
                <div class="px-8 py-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                    <h3 class="text-xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2.5">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Record Payment
                    </h3>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-8">
                    <!-- Summary Card -->
                    <div class="bg-gradient-to-br from-emerald-50/70 to-teal-50/30 dark:from-emerald-950/20 dark:to-teal-950/5 rounded-2xl p-5 mb-6 border border-emerald-100/50 dark:border-emerald-900/35 text-sm shadow-sm">
                        <div class="flex justify-between items-center mb-2.5">
                            <span class="text-emerald-800 dark:text-emerald-400 font-semibold">Student:</span>
                            <span class="font-extrabold text-emerald-950 dark:text-white text-base">{{ $record->student->name }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-emerald-800 dark:text-emerald-400 font-semibold">Bill Period:</span>
                            <span class="font-bold text-emerald-900 dark:text-emerald-250">{{ \Carbon\Carbon::parse($record->period . '-01')->format('F Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-base border-t border-emerald-200/50 dark:border-emerald-900/50 pt-3 mt-3">
                            <span class="text-emerald-800 dark:text-emerald-400 font-bold text-base">Pending Balance:</span>
                            <span class="font-black text-red-600 dark:text-red-400 text-lg">Rs. {{ number_format($record->balance, 2) }}</span>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form wire:submit.prevent="storePayment">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Payment Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="payment_date" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:ring-emerald-500 focus:border-emerald-500 font-medium">
                                @error('payment_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Amount (Rs) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" max="{{ $record->balance }}" wire:model="amount" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 font-bold focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                                @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Pay Head Breakdown -->
                        <div class="mb-5 bg-gray-50/50 dark:bg-gray-900/20 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Pay Head Breakdown</label>
                            <div class="space-y-3">
                                @foreach($record->items as $item)
                                    @if($item->amount > 0)
                                        <div class="flex items-center justify-between gap-3 pb-2.5 border-b border-gray-100 dark:border-gray-700 last:border-b-0 last:pb-0">
                                            <div class="min-w-0 flex-1">
                                                <span class="block font-bold text-gray-800 dark:text-gray-250 text-sm truncate">
                                                    {{ $item->fee_head_name }}
                                                    @if($item->subject_name)
                                                        <span class="text-xs font-normal text-gray-500">({{ $item->subject_name }})</span>
                                                    @endif
                                                </span>
                                                <span class="block text-xs text-gray-400 mt-0.5">
                                                    Total: Rs. {{ number_format($item->amount, 2) }} | Paid: Rs. {{ number_format($item->paid_amount ?? 0, 2) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500">Due: Rs. {{ number_format(($item->balance !== null ? $item->balance : $item->amount), 2) }}</span>
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    max="{{ $item->balance !== null ? $item->balance : $item->amount }}" 
                                                    wire:model.live="itemPayments.{{ $item->id }}" 
                                                    class="w-24 px-2 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-right font-black text-xs text-emerald-600 dark:text-emerald-400 focus:ring-emerald-500 focus:border-emerald-500"
                                                />
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between pb-2.5 border-b border-gray-100 dark:border-gray-700 last:border-b-0 last:pb-0">
                                            <div>
                                                <span class="block font-bold text-red-600 dark:text-red-400 text-sm">{{ $item->fee_head_name }} (Discount)</span>
                                                <span class="block text-xs text-gray-400 mt-0.5">{{ $item->description }}</span>
                                            </div>
                                            <span class="font-extrabold text-red-600 dark:text-red-400 text-sm">-Rs. {{ number_format(abs($item->amount), 2) }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Payment Method <span class="text-red-500">*</span></label>
                                <select wire:model="payment_method" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-semibold">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="card">Credit/Debit Card</option>
                                </select>
                                @error('payment_method') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Reference No.</label>
                                <input type="text" wire:model="reference_number" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-emerald-500 focus:border-emerald-500 text-sm" placeholder="e.g. Cheque / Txn ID">
                                @error('reference_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Remarks</label>
                            <input type="text" wire:model="remarks" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-emerald-500 focus:border-emerald-500 text-sm" placeholder="e.g. Paid by father">
                            @error('remarks') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex justify-end gap-3.5 border-t border-gray-100 dark:border-gray-700 pt-5 mt-5">
                            <button type="button" wire:click="close" class="px-5 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-250 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-xl font-bold transition-all text-sm">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl font-extrabold shadow-lg shadow-emerald-600/20 transition-all text-sm disabled:opacity-75">
                                Confirm Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
