<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Defaulter List</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Students with unpaid or partially paid fee bills</p>
        </div>
        <button onclick="window.print()" class="px-4 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print List
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-red-50 dark:bg-red-900/20 p-5 rounded-xl border border-red-100 dark:border-red-800 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider mb-1">Total Due (Filtered)</p>
                <p class="text-3xl font-black text-red-700 dark:text-red-500">Rs. {{ number_format($totalDueAggregate, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800/50 p-5 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Defaulters</p>
                <p class="text-3xl font-black text-gray-800 dark:text-white">{{ $totalDefaulters }} Students</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6 flex flex-col sm:flex-row gap-4">
        <div class="w-full sm:w-1/3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Class Filter</label>
            <select wire:model.live="filter_class" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
                <option value="">All Classes</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-1/3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Minimum Due Amount</label>
            <input type="number" wire:model.live.debounce.500ms="min_due" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm" placeholder="e.g. 1">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden print-area">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Details</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Unpaid Bills</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Total Due</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right print-hidden">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($defaulters as $def)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $def->student->name }}</div>
                                <div class="text-xs text-gray-500">Admn: {{ $def->student->admission_no }} | Ph: {{ $def->student->phone ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                {{ $def->class->name }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">{{ $def->unpaid_bills }}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-red-600 dark:text-red-400">
                                Rs. {{ number_format($def->total_due, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right print-hidden">
                                <a href="{{ route('admin.fee.ledger', $def->student_id) }}" class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                                    View Ledger
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <p class="text-lg font-medium">No Defaulters Found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($defaulters->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 print-hidden">
                {{ $defaulters->links() }}
            </div>
        @endif
    </div>

    <style>
        @media print {
            .print-hidden {
                display: none !important;
            }
            body {
                background: white !important;
            }
            aside, header {
                display: none !important;
            }
            main {
                margin: 0 !important;
                padding: 0 !important;
            }
            .print-area {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</div>
