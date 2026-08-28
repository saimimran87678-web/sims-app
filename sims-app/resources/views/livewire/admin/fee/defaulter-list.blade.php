<div>
    <!-- Flash Messages (Hidden in Print) -->
    <div class="print:hidden">
        @if (session()->has('message'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm font-semibold flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('message') }}</span>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-sm font-semibold flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- ============================================================
         SCREEN VIEW HEADER & ACTIONS (Hidden in Print)
         ============================================================ -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 mb-6 print:hidden">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Defaulter List</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Manage, export, and notify students with unpaid or outstanding fee balances</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 w-full xl:w-auto">
            <!-- Send Bulk Reminders Main Button -->
            <button 
                wire:click="sendBulkReminders" 
                wire:loading.attr="disabled"
                title="Send personalized fee reminder messages & voucher links to all defaulter parents"
                class="flex-1 sm:flex-none px-4 py-2.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-2 shadow-sm shadow-amber-600/20 transition-all duration-200 disabled:opacity-50"
            >
                <svg wire:loading.remove wire:target="sendBulkReminders" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <svg wire:loading wire:target="sendBulkReminders" class="animate-spin w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Notify All Defaulters</span>
            </button>

            <!-- Export Excel (.xls) Button -->
            <button 
                wire:click="exportExcel" 
                wire:loading.attr="disabled"
                title="Download formatted Excel Spreadsheet (.xls)"
                class="flex-1 sm:flex-none px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-2 shadow-sm shadow-emerald-600/20 transition-all duration-200 disabled:opacity-50"
            >
                <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Export Excel</span>
            </button>

            <!-- Export CSV (.csv) Button -->
            <button 
                wire:click="exportCsv" 
                wire:loading.attr="disabled"
                title="Download raw CSV file (.csv)"
                class="flex-1 sm:flex-none px-3.5 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-1.5 shadow-sm shadow-teal-600/20 transition-all duration-200 disabled:opacity-50"
            >
                <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Export CSV</span>
            </button>

            <!-- Optimized Print Button -->
            <button 
                onclick="window.print()" 
                class="flex-1 sm:flex-none px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-2 shadow-sm shadow-indigo-600/20 transition-all duration-200"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                <span>Print List</span>
            </button>
        </div>
    </div>

    <!-- ============================================================
         SCREEN STATS & FILTERS (Hidden in Print)
         ============================================================ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-6 print:hidden">
        <div class="bg-red-50 dark:bg-red-900/20 p-5 rounded-2xl border border-red-100 dark:border-red-800/50 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider mb-1">Total Due (Filtered)</p>
                <p class="text-2xl sm:text-3xl font-black text-red-700 dark:text-red-400">Rs. {{ number_format($totalDueAggregate, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-800/40 text-red-600 dark:text-red-300 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-5 rounded-2xl border border-indigo-100 dark:border-indigo-800/50 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">Total Defaulter Students</p>
                <p class="text-2xl sm:text-3xl font-black text-indigo-900 dark:text-indigo-200">{{ $totalDefaulters }} Students</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-800/40 text-indigo-600 dark:text-indigo-300 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Filters Container -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-5 mb-6 flex flex-col sm:flex-row gap-4 print:hidden">
        <div class="w-full sm:w-1/2">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Class Filter</label>
            <select wire:model.live="filter_class" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-gray-800 transition-all">
                <option value="">All Classes</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-1/2">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Minimum Due Amount (Rs.)</label>
            <input type="number" wire:model.live.debounce.500ms="min_due" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-gray-800 transition-all" placeholder="e.g. 1000">
        </div>
    </div>

    <!-- ============================================================
         SCREEN PAGINATED TABLE (Hidden in Print)
         ============================================================ -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden print:hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-gray-50/80 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Student Details</th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Unpaid Bills</th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Due Balance</th>
                        <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($defaulters as $def)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-900 dark:text-white text-base">{{ $def->student ? $def->student->name : 'N/A' }}</div>
                                <div class="text-xs font-medium text-gray-500 mt-0.5">
                                    Admn: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $def->student ? $def->student->admission_no : 'N/A' }}</span> 
                                    @if($def->student && $def->student->roll_no)
                                        | Roll: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $def->student->roll_no }}</span>
                                    @endif
                                    | Ph: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $def->student ? ($def->student->phone ?? 'N/A') : 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-800 dark:text-gray-200 font-semibold text-sm">
                                {{ $def->class ? $def->class->name : 'N/A' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                    {{ $def->unpaid_bills }} {{ Str::plural('Bill', $def->unpaid_bills) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-extrabold text-red-600 dark:text-red-400 text-base">
                                Rs. {{ number_format($def->total_due, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Notification Bell Icon Button to queue reminder -->
                                    <button 
                                        wire:click="sendSingleReminder({{ $def->student_id }})"
                                        wire:loading.attr="disabled"
                                        title="Send friendly fee reminder to parent via WhatsApp"
                                        class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 dark:text-amber-300 transition-colors border border-amber-200 dark:border-amber-800/40 disabled:opacity-50"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    </button>

                                    <!-- View Ledger Action Button -->
                                    <a href="{{ route('admin.fee.ledger', $def->student_id) }}" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-bold text-xs bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors">
                                        <span>Ledger</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="max-w-xs mx-auto text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-base font-bold text-gray-700 dark:text-gray-300">No Defaulters Found</p>
                                    <p class="text-xs text-gray-500 mt-1">All students are up-to-date with fee payments under the selected filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($defaulters->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                {{ $defaulters->links() }}
            </div>
        @endif
    </div>

    <!-- ============================================================
         PRINT-ONLY OPTIMIZED REPORT LAYOUT (Visible ONLY during print)
         ============================================================ -->
    <div class="hidden print:block print-container">
        <!-- Print Header -->
        <div class="border-b-2 border-gray-800 pb-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight text-black">
                        {{ \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2 ISLAMABAD') }}
                    </h1>
                    <p class="text-xs font-semibold text-gray-700 uppercase tracking-widest mt-0.5">
                        {{ \App\Models\Setting::getGlobal('institute_address', 'Federal Board Educational Campus, Islamabad') }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 bg-black text-white text-xs font-extrabold uppercase tracking-widest rounded">
                        Defaulters Report
                    </span>
                    <p class="text-[10px] text-gray-600 font-bold mt-1">
                        Printed: {{ now()->format('d-M-Y h:i A') }}
                    </p>
                </div>
            </div>

            <!-- Report Meta Grid -->
            <div class="grid grid-cols-4 gap-2 mt-4 pt-3 border-t border-gray-300 text-xs">
                <div>
                    <span class="text-gray-500 font-semibold block text-[10px] uppercase">Session</span>
                    <span class="font-extrabold text-black">{{ $selectedSessionName }}</span>
                </div>
                <div>
                    <span class="text-gray-500 font-semibold block text-[10px] uppercase">Shift</span>
                    <span class="font-extrabold text-black">{{ $currentShift }}</span>
                </div>
                <div>
                    <span class="text-gray-500 font-semibold block text-[10px] uppercase">Class Filter</span>
                    <span class="font-extrabold text-black">{{ $selectedClassName }}</span>
                </div>
                <div>
                    <span class="text-gray-500 font-semibold block text-[10px] uppercase">Min Due Threshold</span>
                    <span class="font-extrabold text-black">>= Rs. {{ number_format((float)($min_due ?: 0), 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Summary Banner -->
        <div class="flex items-center justify-between bg-gray-100 p-3 border border-gray-400 mb-4 rounded">
            <div class="text-xs">
                <span class="font-bold text-gray-700">Total Defaulters:</span>
                <span class="font-black text-black ml-1 text-sm">{{ $totalDefaulters }} Students</span>
            </div>
            <div class="text-xs text-right">
                <span class="font-bold text-gray-700">Aggregate Outstanding Balance:</span>
                <span class="font-black text-black ml-1 text-base">Rs. {{ number_format($totalDueAggregate, 2) }}</span>
            </div>
        </div>

        <!-- Complete Unpaginated Defaulter Table for Print -->
        <table class="w-full text-left border-collapse print-table">
            <thead>
                <tr class="bg-gray-200 border-y-2 border-black">
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400 text-center w-8">#</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400">Admn No</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400">Roll No</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400">Student Name</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400">Father Name</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400">Class</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400">Contact / Phone</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase border-r border-gray-400 text-center">Unpaid</th>
                    <th class="py-2 px-2 text-[10px] font-extrabold text-black uppercase text-right">Total Due (Rs.)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-300">
                @forelse($allDefaulters as $index => $def)
                    <tr class="border-b border-gray-300">
                        <td class="py-1.5 px-2 text-[11px] font-bold text-center border-r border-gray-300">{{ $index + 1 }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-medium border-r border-gray-300">{{ $def->student ? $def->student->admission_no : 'N/A' }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-medium border-r border-gray-300 text-center">{{ $def->student ? ($def->student->roll_no ?? '-') : '-' }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-bold text-black border-r border-gray-300">{{ $def->student ? $def->student->name : 'N/A' }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-medium border-r border-gray-300">{{ $def->student ? ($def->student->father_name ?? '-') : '-' }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-semibold border-r border-gray-300">{{ $def->class ? $def->class->name : 'N/A' }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-medium border-r border-gray-300">{{ $def->student ? ($def->student->phone ?? 'N/A') : 'N/A' }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-bold text-center border-r border-gray-300">{{ $def->unpaid_bills }}</td>
                        <td class="py-1.5 px-2 text-[11px] font-black text-right text-black">Rs. {{ number_format($def->total_due, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-6 text-center text-xs font-bold text-gray-500">No Defaulters Found</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-gray-200 border-t-2 border-black font-black text-black">
                    <td colspan="8" class="py-2.5 px-3 text-xs uppercase text-right border-r border-gray-400">Total Aggregate Outstanding Balance:</td>
                    <td class="py-2.5 px-2 text-xs text-right text-black">Rs. {{ number_format($totalDueAggregate, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures & Verification Footer -->
        <div class="mt-12 pt-6 border-t border-gray-400 grid grid-cols-2 gap-8 text-xs font-bold text-gray-800">
            <div>
                <p class="text-gray-500 text-[10px] uppercase">Prepared / Verified By</p>
                <p class="mt-8 border-t border-black pt-1 w-48 font-bold text-black">{{ Auth::user()->name }} (Accounts Dept)</p>
            </div>
            <div class="text-right flex flex-col items-end">
                <p class="text-gray-500 text-[10px] uppercase">Principal / Authorized Signature</p>
                <p class="mt-8 border-t border-black pt-1 w-48 text-center font-bold text-black">Stamp & Signature</p>
            </div>
        </div>
    </div>

    <!-- Printable CSS rules -->
    <style>
        @media print {
            /* Hide all page framework elements */
            body, html {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            aside, header, footer, .no-print, .print-hidden, #license-locked-modal, .fixed, button {
                display: none !important;
            }
            main {
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                height: auto !important;
            }
            /* Table formatting */
            .print-table {
                border-collapse: collapse !important;
                width: 100% !important;
            }
            .print-table th, .print-table td {
                border: 1px solid #64748b !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .print-table tr {
                page-break-inside: avoid !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
        }
    </style>
</div>
