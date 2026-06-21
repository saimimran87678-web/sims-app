<div>
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.fee.defaulters') }}" class="p-2 text-gray-500 bg-white dark:bg-gray-800 rounded-full hover:bg-gray-100 transition-colors shadow-sm border border-gray-200 dark:border-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Student Ledger</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $student->name }} (Admn: {{ $student->admission_no }}) - {{ $student->class->name }}</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Billed</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">Rs. {{ number_format($totalBilled, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Paid</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">Rs. {{ number_format($totalPaid, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-red-100 dark:border-red-900/30">
            <p class="text-sm font-semibold text-red-500 uppercase tracking-wider mb-1">Current Balance</p>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">Rs. {{ number_format($totalBalance, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Bills Section -->
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Fee Bills
            </h3>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Period</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Amount</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Balance</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($records as $rec)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($rec->period . '-01')->format('M Y') }}</div>
                                        <div class="text-[10px] text-gray-500 capitalize">{{ str_replace('_', ' ', $rec->cycle) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">Rs. {{ number_format($rec->total_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-red-600">Rs. {{ number_format($rec->balance, 2) }}</td>
                                    <td class="px-4 py-3">
                                        @if($rec->status === 'paid')
                                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-green-100 text-green-800">Paid</span>
                                        @elseif($rec->period > now()->format('Y-m'))
                                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Upcoming</span>
                                        @elseif($rec->status === 'partial')
                                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-yellow-100 text-yellow-800">Partial</span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-red-100 text-red-800">Unpaid</span>
                                        @endif
                                        <a href="{{ route('admin.fee.invoice.download', $rec->id) }}" target="_blank" class="ml-2 text-blue-600 hover:text-blue-800" title="Download Invoice">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        </a>
                                        @if($rec->status !== 'paid')
                                            <button wire:click="$dispatch('openPaymentModal', { recordId: {{ $rec->id }} })" class="ml-2 text-emerald-600 hover:text-emerald-800" title="Record Payment">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">No bills generated for this student.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Section -->
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Payment History
            </h3>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Method</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase">For Bill</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($payments as $pay)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $pay->payment_date->format('d M, Y') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm capitalize">{{ str_replace('_', ' ', $pay->payment_method) }}</div>
                                        @if($pay->reference_number)
                                            <div class="text-[10px] text-gray-500">Ref: {{ $pay->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ \Carbon\Carbon::parse($pay->record->period . '-01')->format('M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-green-600 dark:text-green-400">
                                        Rs. {{ number_format($pay->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">No payments recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <livewire:admin.fee.record-payment />
</div>
