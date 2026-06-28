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

    <!-- Unified Fee Ledger & Transaction History -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-150 dark:border-gray-700 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-150 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Fee Ledger & Transaction History
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-150 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Billing Period</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Amount to Pay</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payments Received</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Remaining Balance</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150 dark:divide-gray-700">
                    @forelse($records as $rec)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($rec->period . '-01')->format('F Y') }}</div>
                                <div class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $rec->cycle) }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                Rs. {{ number_format($rec->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($rec->payments->isEmpty())
                                    <span class="text-gray-400 dark:text-gray-600 text-sm">—</span>
                                @else
                                    <div class="flex flex-col gap-1.5 max-w-xs">
                                        @foreach($rec->payments as $payment)
                                            <div class="flex items-center justify-between gap-3 bg-emerald-50/50 dark:bg-emerald-950/20 px-2 py-1 rounded border border-emerald-100/50 dark:border-emerald-900/30">
                                                <span class="font-extrabold text-emerald-600 dark:text-emerald-400 text-xs">+ {{ number_format($payment->amount_paid, 2) }}</span>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">
                                                        {{ $payment->payment_date->format('d M, Y') }} ({{ ucfirst($payment->payment_method) }})
                                                    </span>
                                                    <a href="{{ route('admin.fee.receipt.download', $payment->id) }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" title="Download Receipt">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-extrabold whitespace-nowrap {{ $rec->balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                Rs. {{ number_format($rec->balance, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($rec->status === 'paid')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">Paid</span>
                                @elseif($rec->period > now()->format('Y-m'))
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Upcoming</span>
                                @elseif($rec->status === 'partial')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">Partial</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">Unpaid</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex justify-end items-center gap-3">
                                    <a href="{{ route('admin.fee.invoice.download', $rec->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:hover:bg-blue-900/45 dark:text-blue-400 text-xs font-bold rounded-lg transition-colors" title="Download Invoice">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Invoice
                                    </a>
                                    <button onclick="printPdf('{{ route('admin.fee.invoice.download', $rec->id) }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/45 dark:text-indigo-400 text-xs font-bold rounded-lg transition-colors" title="Print Invoice Directly">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        Print
                                    </button>
                                    @if($rec->status !== 'paid')
                                        <button wire:click="$dispatch('openPaymentModal', { recordId: {{ $rec->id }} })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/45 dark:text-emerald-400 text-xs font-bold rounded-lg transition-colors" title="Collect Payment">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Collect
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No ledger records found for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <livewire:admin.fee.record-payment />
</div>
