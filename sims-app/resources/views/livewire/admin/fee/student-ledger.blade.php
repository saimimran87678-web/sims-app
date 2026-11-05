<div>
    {{-- Top Action Bar & Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.fee.defaulters') }}" class="p-2 text-gray-500 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-100 transition-colors shadow-sm border border-gray-200 dark:border-gray-700" title="Back to Defaulter List">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>Universal Fee Ledger</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400 border border-indigo-200/50">Console</span>
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Real-time institutional running balance, sequential receipts & audit-controlled voids</p>
                </div>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
            {{-- Pending Approvals Queue Button (Superadmin / Principal) --}}
            @can('fees.void')
                <button 
                    wire:click="openApprovalsModal" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl {{ $pendingRequestsCount > 0 ? 'bg-amber-500 text-white shadow-md animate-pulse hover:bg-amber-600' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200' }} transition-all"
                    title="Review pending payment void requests from cashiers"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Void Approvals</span>
                    @if($pendingRequestsCount > 0)
                        <span class="px-1.5 py-0.2 text-[10px] bg-white text-amber-700 font-black rounded-full">{{ $pendingRequestsCount }}</span>
                    @endif
                </button>

                {{-- Void Policy & Security Button --}}
                <button 
                    wire:click="openPolicyModal" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 transition-colors shadow-sm border border-slate-200/60 dark:border-slate-700"
                    title="Configure Institutional Void Authorization Policy"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Void Policy</span>
                </button>
            @endcan

            {{-- Day-End Cashier Closing Button --}}
            <button 
                wire:click="openDayEndModal" 
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50 border border-amber-200/60 dark:border-amber-800 transition-colors shadow-sm"
            >
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Day-End Closing</span>
            </button>

            {{-- Collect Payment Shortcut --}}
            <a 
                href="{{ route('admin.fee.record-payment') }}" 
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                <span>Collect Fees</span>
            </a>

            {{-- Print Statement --}}
            @if($student)
                <button 
                    onclick="window.print()" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-gray-700 transition-colors shadow-sm"
                    title="Print Statement of Account"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Print Ledger</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session()->has('message'))
        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between text-sm font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">&times;</button>
        </div>
    @endif

    {{-- Policy Disabled Warning Banner --}}
    @if(!$policyVoidEnabled)
        <div class="mb-4 p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-900 flex items-center justify-between text-xs font-semibold">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Fee Payment Voiding is currently <strong>DISABLED</strong> by institutional administrative policy. No payment cancellations can be performed.</span>
            </div>
            @can('fees.void')
                <button wire:click="openPolicyModal" class="underline text-amber-700 hover:text-amber-950 font-bold ml-2">Enable Policy</button>
            @endcan
        </div>
    @endif

    {{-- Instant Student Switcher Bar --}}
    <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700 mb-6">
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.250ms="searchStudent"
                        placeholder="Type Student Name, Admission No, or Roll Number to switch ledger instantly..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/60 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                    >
                </div>
                @if($student)
                    <div class="hidden sm:flex items-center gap-2 bg-indigo-50/80 dark:bg-indigo-950/40 px-3 py-2 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-black flex items-center justify-center text-xs">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <div class="text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-indigo-900 dark:text-indigo-200 block">{{ $student->name }}</span>
                                @if($student->trashed())
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-700">Archived Profile</span>
                                @endif
                            </div>
                            <span class="text-indigo-600/80 dark:text-indigo-400 text-[10px]">Adm: {{ $student->admission_no ?? 'N/A' }} &bull; {{ $student->class?->name ?? 'No Class' }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Live Search Autocomplete Dropdown --}}
            @if(!empty($searchResults))
                <div class="absolute z-30 left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($searchResults as $res)
                        <button 
                            type="button" 
                            wire:click="selectStudent({{ $res['id'] }})"
                            class="w-full text-left px-4 py-3 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/30 flex items-center justify-between transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                    {{ substr($res['name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $res['name'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        Adm: {{ $res['admission_no'] ?? 'N/A' }} &bull; Class: {{ $res['class']['name'] ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/40 px-2.5 py-1 rounded-lg">
                                Switch Ledger &rarr;
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($student && $ledgerData)
        {{-- Student Profile & Financial KPI Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
            {{-- Total Billed --}}
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Invoiced</span>
                <div class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
                    Rs. {{ number_format($ledgerData['summary']['totalBilled'], 2) }}
                </div>
                <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1 font-medium">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>All Issued Vouchers</span>
                </div>
            </div>

            {{-- Total Paid (Net Valid) --}}
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block mb-1">Total Paid (Cleared)</span>
                <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
                    Rs. {{ number_format($ledgerData['summary']['totalPaid'], 2) }}
                </div>
                <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Non-Voided Receipts</span>
                </div>
            </div>

            {{-- Total Voided (Audited) --}}
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider block mb-1">Total Voided / Cancelled</span>
                <div class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                    Rs. {{ number_format($ledgerData['summary']['totalVoided'], 2) }}
                </div>
                <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1 font-medium">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Reversed by Supervisor</span>
                </div>
            </div>

            {{-- Current Net Balance --}}
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl shadow-sm border {{ $ledgerData['summary']['currentBalance'] > 0 ? 'border-red-200 dark:border-red-900/40 bg-red-50/20' : 'border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/20' }}">
                <span class="text-[11px] font-bold {{ $ledgerData['summary']['currentBalance'] > 0 ? 'text-red-500' : 'text-emerald-600' }} uppercase tracking-wider block mb-1">Outstanding Balance</span>
                <div class="text-xl sm:text-2xl font-black {{ $ledgerData['summary']['currentBalance'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    Rs. {{ number_format($ledgerData['summary']['currentBalance'], 2) }}
                </div>
                <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1 font-medium">
                    @if($ledgerData['summary']['currentBalance'] > 0)
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span class="text-red-600 dark:text-red-400 font-bold">Dues Outstanding</span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">All Dues Cleared</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Running Balance Ledger Table Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700 overflow-hidden mb-8">
            {{-- Filter Bar --}}
            <div class="p-4 sm:p-5 border-b border-gray-150 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50 dark:bg-gray-900/30">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Filter Ledger:</span>
                    <div class="inline-flex rounded-xl p-1 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex-wrap">
                        <button 
                            wire:click="$set('ledgerFilter', 'all')" 
                            class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $ledgerFilter === 'all' ? 'bg-white dark:bg-gray-700 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                        >
                            All Entries
                        </button>
                        <button 
                            wire:click="$set('ledgerFilter', 'invoices')" 
                            class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $ledgerFilter === 'invoices' ? 'bg-white dark:bg-gray-700 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                        >
                            Invoices (Debit)
                        </button>
                        <button 
                            wire:click="$set('ledgerFilter', 'payments')" 
                            class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $ledgerFilter === 'payments' ? 'bg-white dark:bg-gray-700 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                        >
                            Payments (Credit)
                        </button>
                        <button 
                            wire:click="$set('ledgerFilter', 'voided')" 
                            class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $ledgerFilter === 'voided' ? 'bg-white dark:bg-gray-700 text-amber-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                        >
                            Voided (Audits)
                        </button>
                        <button 
                            wire:click="$set('ledgerFilter', 'pending_void')" 
                            class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $ledgerFilter === 'pending_void' ? 'bg-white dark:bg-gray-700 text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                        >
                            Pending Approvals
                        </button>
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    Showing {{ count($filteredTimeline) }} transaction records
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-150 dark:border-gray-700 text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-5 py-3.5">Date & Period</th>
                            <th class="px-5 py-3.5">Transaction Type & Ref #</th>
                            <th class="px-5 py-3.5 text-right text-blue-600 dark:text-blue-400">Debit (Billed)</th>
                            <th class="px-5 py-3.5 text-right text-emerald-600 dark:text-emerald-400">Credit (Paid)</th>
                            <th class="px-5 py-3.5 text-right">Running Balance</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Action / Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-150 dark:divide-gray-700 text-sm">
                        @forelse($filteredTimeline as $item)
                            @php
                                $isInvoice = ($item['type'] === 'invoice');
                                $isPayment = ($item['type'] === 'payment');
                                $isVoid = !empty($item['is_void']);
                                $voidStatus = $item['void_status'] ?? ($isVoid ? 'approved' : 'none');
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40 {{ $isVoid ? 'bg-amber-50/20 dark:bg-amber-950/10' : ($voidStatus === 'pending' ? 'bg-orange-50/30 dark:bg-orange-950/20' : '') }}">
                                {{-- Date & Period --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900 dark:text-white text-xs">
                                        {{ date('d M, Y', strtotime($item['date'])) }}
                                    </div>
                                    <div class="text-[11px] text-gray-400 font-mono">
                                        {{ date('h:i A', strtotime($item['date'])) }} &bull; {{ $item['period_label'] }}
                                    </div>
                                </td>

                                {{-- Transaction Type & Ref # --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if($isInvoice)
                                            <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs">
                                                Dr
                                            </span>
                                            <div>
                                                <span class="font-bold text-gray-900 dark:text-white text-xs block">{{ $item['title'] }}</span>
                                                <span class="text-[10px] text-gray-400 font-mono">{{ $item['ref_no'] }}</span>
                                            </div>
                                        @else
                                            <span class="w-6 h-6 rounded-lg {{ $isVoid ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} font-bold flex items-center justify-center text-xs">
                                                Cr
                                            </span>
                                            <div>
                                                <span class="font-bold {{ $isVoid ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-white' }} text-xs block">
                                                    {{ $item['title'] }}
                                                </span>
                                                <span class="text-[10px] text-gray-400 font-mono">
                                                    {{ $item['ref_no'] }} &bull; {{ strtoupper($item['payment']->payment_method ?? 'CASH') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Debit (Billed) --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    @if($isInvoice)
                                        <span class="font-black text-blue-600 dark:text-blue-400 text-xs">
                                            + Rs. {{ number_format($item['debit'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">&mdash;</span>
                                    @endif
                                </td>

                                {{-- Credit (Paid) --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    @if($isPayment)
                                        @if($isVoid)
                                            <span class="font-bold text-gray-400 line-through text-xs" title="Voided - Not counted toward ledger credit">
                                                - Rs. {{ number_format($item['credit'], 2) }}
                                            </span>
                                        @else
                                            <span class="font-black text-emerald-600 dark:text-emerald-400 text-xs">
                                                - Rs. {{ number_format($item['credit'], 2) }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">&mdash;</span>
                                    @endif
                                </td>

                                {{-- Running Balance --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <span class="font-mono font-black text-xs {{ $item['running_balance'] > 0 ? 'text-gray-900 dark:text-white' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        Rs. {{ number_format($item['running_balance'], 2) }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if($isVoid)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200">
                                            VOIDED
                                        </span>
                                    @elseif($voidStatus === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300 border border-orange-200 animate-pulse" title="Requested by {{ $item['void_requested_by'] ?? 'Staff' }} on {{ date('d M, Y', strtotime($item['void_requested_at'])) }}">
                                            PENDING VOID
                                        </span>
                                    @elseif($voidStatus === 'rejected')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200" title="Void request rejected: {{ $item['void_rejection_reason'] }}">
                                            VOID REJECTED
                                        </span>
                                    @elseif($isInvoice)
                                        @if($item['record']->status === 'paid')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                PAID
                                            </span>
                                        @elseif($item['record']->status === 'partial')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-yellow-100 text-yellow-800 dark:bg-yellow-950/60 dark:text-yellow-300 border border-yellow-200">
                                                PARTIAL
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 border border-red-200">
                                                UNPAID
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            CLEARED
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions & Audit --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($isInvoice)
                                            <a href="{{ route('admin.fee.invoice.download', $item['record']->id) }}" target="_blank" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors" title="Download Invoice">
                                                Invoice
                                            </a>
                                            @if($item['record']->status !== 'paid')
                                                <button wire:click="$dispatch('openPaymentModal', { recordId: {{ $item['record']->id }} })" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors" title="Collect Fees">
                                                    Collect
                                                </button>
                                            @endif
                                        @elseif($isPayment)
                                            <a href="{{ route('admin.fee.receipt.download', $item['payment']->id) }}" target="_blank" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors" title="Download Receipt">
                                                Receipt
                                            </a>

                                            @if($voidStatus === 'pending')
                                                {{-- Pending Void Request Actions --}}
                                                @can('fees.void')
                                                    <button 
                                                        wire:click="promptAuthorizeRequest({{ $item['payment']->id }})" 
                                                        class="px-2 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                                        title="Authorize Pending Void"
                                                    >
                                                        Authorize
                                                    </button>
                                                    <button 
                                                        wire:click="openRejectModal({{ $item['payment']->id }})" 
                                                        class="px-2 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors"
                                                        title="Reject Void Request"
                                                    >
                                                        Reject
                                                    </button>
                                                @else
                                                    <span class="text-[10px] text-orange-600 font-semibold italic">Pending Principal Sign-off</span>
                                                @endcan
                                            @elseif(!$isVoid)
                                                @if(!$policyVoidEnabled)
                                                    <button disabled class="px-2 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed" title="Voiding is disabled by institutional policy">
                                                        Void
                                                    </button>
                                                @elseif(auth()->user()->can('fees.void') || auth()->user()->hasRole('Super Admin') || auth()->id() === 1)
                                                    <button 
                                                        wire:click="openVoidModal({{ $item['payment']->id }})" 
                                                        class="px-2 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200 transition-colors"
                                                        title="Supervisor Void Payment"
                                                    >
                                                        Void
                                                    </button>
                                                @else
                                                    {{-- Non-superadmin void request button --}}
                                                    <button 
                                                        wire:click="openVoidModal({{ $item['payment']->id }})" 
                                                        class="px-2 py-1 text-xs font-bold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-colors"
                                                        title="Request Supervisor Void"
                                                    >
                                                        Request Void...
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-[10px] text-gray-400 italic max-w-[140px] truncate block text-right" title="Voided by {{ $item['voided_by'] ?? 'Admin' }}: {{ $item['void_reason'] }}">
                                                    {{ $item['void_reason'] }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-xs font-semibold">
                                    No ledger transactions found matching the selected filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <h3 class="text-base font-bold text-gray-700 dark:text-gray-300">Select a Student to View Ledger</h3>
            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Use the search switcher above to look up any student by Name, Roll Number, or Admission Number.</p>
        </div>
    @endif

    {{-- Void Modal (Cashier Request / Supervisor Walk-Up / Superadmin Direct) --}}
    @if($showVoidModal)
        @php
            $isSuperAdmin = auth()->user()->hasRole('Super Admin') || auth()->id() === 1 || auth()->user()->can('fees.void');
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm" x-data x-trap="true">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="p-6">
                    {{-- Warning Icon & Title --}}
                    <div class="flex items-center gap-3.5 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 font-bold flex items-center justify-center shrink-0 border border-rose-200 dark:border-rose-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white">
                                {{ $isSuperAdmin ? 'Authorize Payment Void' : ($voidMode === 'request' ? 'Submit Void Request' : 'Supervisor Override Void') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Institutional reversal & ledger audit log</p>
                        </div>
                    </div>

                    {{-- Transaction Details Card --}}
                    <div class="bg-gray-50 dark:bg-gray-900/60 p-3.5 rounded-2xl border border-gray-200 dark:border-gray-700 mb-4 text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Receipt Number:</span>
                            <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $voidPaymentReceiptNumber }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Amount to Cancel:</span>
                            <span class="font-bold text-rose-600">Rs. {{ number_format($voidPaymentAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Student:</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ $voidPaymentStudentName }}</span>
                        </div>
                    </div>

                    {{-- Mode Switcher for Regular Staff --}}
                    @if(!$isSuperAdmin && $policyAllowRequests)
                        <div class="flex rounded-xl p-1 bg-gray-100 dark:bg-gray-700/60 mb-4 text-xs font-bold border border-gray-200 dark:border-gray-600">
                            <button 
                                type="button" 
                                wire:click="$set('voidMode', 'request')" 
                                class="flex-1 py-1.5 rounded-lg transition-all {{ $voidMode === 'request' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                            >
                                1. Submit for Approval
                            </button>
                            <button 
                                type="button" 
                                wire:click="$set('voidMode', 'supervisor')" 
                                class="flex-1 py-1.5 rounded-lg transition-all {{ $voidMode === 'supervisor' ? 'bg-white dark:bg-gray-800 text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                            >
                                2. Walk-Up Supervisor Sign-Off
                            </button>
                        </div>
                    @endif

                    <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-[11px] mb-4">
                        @if($isSuperAdmin || $voidMode === 'supervisor')
                            <strong>Accounting Warning:</strong> This will immediately cancel receipt <strong>{{ $voidPaymentReceiptNumber }}</strong> and restore <strong>Rs. {{ number_format($voidPaymentAmount, 2) }}</strong> to student dues.
                        @else
                            <strong>Maker-Checker Workflow:</strong> Your void request will be placed in the <strong>Superadmin / Principal Approval Queue</strong> for official sign-off.
                        @endif
                    </div>

                    @if($voidError)
                        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl font-semibold">
                            {{ $voidError }}
                        </div>
                    @endif

                    <div class="space-y-3.5">
                        {{-- Reason Preset --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Void Reason Category *</label>
                            <select wire:model="voidReasonType" class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-2.5 px-3 font-semibold text-gray-800 dark:text-white">
                                <option value="Entered Wrong Amount">Entered Wrong Amount</option>
                                <option value="Wrong Student Selected">Wrong Student Selected</option>
                                <option value="Duplicate Payment Entry">Duplicate Payment Entry</option>
                                <option value="Payment Bounced / Cancelled">Payment Bounced / Cancelled</option>
                                <option value="Parent Concession Applied Retroactively">Parent Concession Applied Retroactively</option>
                                <option value="Other">Other Operational Correction</option>
                            </select>
                        </div>

                        {{-- Specific Explanatory Note --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Audit Notes / Explanation *</label>
                            <textarea wire:model="voidReasonNotes" rows="2" placeholder="Explain why this void is requested/authorized..." class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-2.5 font-medium text-gray-800 dark:text-white"></textarea>
                        </div>

                        {{-- Walk-Up Supervisor Override Credentials --}}
                        @if(!$isSuperAdmin && $voidMode === 'supervisor')
                            <div class="p-3 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-900/50 space-y-2.5">
                                <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-200 block">Supervisor Sign-Off Details</span>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 dark:text-gray-400 mb-1">Select Authorizing Supervisor *</label>
                                    <select wire:model="selectedSupervisorId" class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-1.5 px-2.5 font-medium">
                                        @foreach($supervisors as $sup)
                                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 dark:text-gray-400 mb-1">Supervisor Password *</label>
                                    <input 
                                        type="password" 
                                        wire:model="supervisorPassword" 
                                        placeholder="Supervisor enters password to sign off"
                                        class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-1.5 px-2.5"
                                    >
                                </div>
                            </div>
                        @elseif($isSuperAdmin && $policyRequirePin)
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Administrator Password Confirmation *
                                </label>
                                <input 
                                    type="password" 
                                    wire:model="supervisorPassword" 
                                    placeholder="Enter your administrator password"
                                    class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-2.5 px-3 font-medium text-gray-800 dark:text-white"
                                >
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button 
                            type="button" 
                            wire:click="closeVoidModal" 
                            class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            wire:click="confirmVoid" 
                            class="px-5 py-2 text-xs font-black text-white {{ (!$isSuperAdmin && $voidMode === 'request') ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-rose-600 hover:bg-rose-700' }} rounded-xl shadow-md transition-all flex items-center gap-1.5"
                        >
                            @if(!$isSuperAdmin && $voidMode === 'request')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                <span>Submit Request</span>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                <span>Authorize & Void Receipt</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Pending Void Approvals Management Modal (Superadmin / Principal) --}}
    @if($showApprovalsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm" x-data x-trap="true">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-3xl w-full overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-150 dark:border-gray-700 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 font-bold flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Pending Void Authorizations Queue</h3>
                                <p class="text-xs text-gray-500">Maker-Checker Authorization for front-desk receipt cancellations</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeApprovalsModal" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                    </div>

                    @if($approvalError)
                        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl font-semibold">
                            {{ $approvalError }}
                        </div>
                    @endif

                    @if($approvingPaymentId)
                        {{-- Confirm Authorization Password Challenge --}}
                        <div class="mb-5 p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 dark:bg-indigo-950/40 dark:border-indigo-900">
                            <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200 block mb-1">Confirm Void Authorization</span>
                            <p class="text-[11px] text-gray-500 mb-3">Enter your Superadmin / Principal password to officially certify this receipt reversal.</p>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="password" 
                                    wire:model="approvalPassword" 
                                    placeholder="Enter your administrator password"
                                    class="text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-2 px-3 font-medium flex-1"
                                >
                                <button 
                                    type="button" 
                                    wire:click="confirmAuthorizeRequest" 
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm"
                                >
                                    Certify & Reverse
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="$set('approvingPaymentId', null)" 
                                    class="px-3 py-2 text-xs font-semibold text-gray-500 hover:bg-gray-100 rounded-xl"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="overflow-x-auto max-h-[420px]">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-150 text-[10px] font-black text-gray-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">Receipt / Date</th>
                                    <th class="px-4 py-3">Student & Class</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3">Requested By & Reason</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($pendingRequests as $req)
                                    <tr class="hover:bg-gray-50/60">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="font-mono font-bold text-indigo-600 block">{{ $req->receipt_number }}</span>
                                            <span class="text-[10px] text-gray-400">{{ $req->payment_date ? $req->payment_date->format('d M, Y') : 'N/A' }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-bold text-gray-900 dark:text-white block">{{ $req->student?->name }}</span>
                                            <span class="text-[10px] text-gray-400">Adm: {{ $req->student?->admission_no }} &bull; {{ $req->record?->class?->name ?? 'Class' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <span class="font-black text-rose-600">Rs. {{ number_format($req->amount_paid, 2) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-[11px] font-bold text-gray-800 dark:text-gray-200">{{ $req->void_reason }}</div>
                                            <div class="text-[10px] text-gray-400">By: {{ $req->voidRequestedByUser?->name ?? 'Cashier' }} on {{ $req->void_requested_at ? $req->void_requested_at->format('d M, h:i A') : '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button 
                                                    type="button" 
                                                    wire:click="promptAuthorizeRequest({{ $req->id }})" 
                                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[11px] shadow-sm"
                                                >
                                                    Authorize
                                                </button>
                                                <button 
                                                    type="button" 
                                                    wire:click="openRejectModal({{ $req->id }})" 
                                                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-lg text-[11px]"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-xs">
                                            No pending void authorization requests at this time.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-150 dark:border-gray-700 mt-4">
                        <button type="button" wire:click="closeApprovalsModal" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Rejection Justification Modal --}}
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm" x-data x-trap="true">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-md w-full p-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Reject Void Request</h3>
                <p class="text-xs text-gray-500 mb-4">Provide a reason explaining why this payment cancellation is rejected.</p>

                @if($rejectionError)
                    <div class="mb-3 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl font-semibold">
                        {{ $rejectionError }}
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Rejection Justification *</label>
                    <textarea wire:model="rejectionReasonText" rows="3" placeholder="e.g. Verified with bank statement: payment cleared properly..." class="w-full text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-2.5 font-medium"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="closeRejectModal" class="px-3.5 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Cancel
                    </button>
                    <button type="button" wire:click="confirmRejectRequest" class="px-4 py-2 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm">
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Institutional Fee Void Policy Modal (Superadmin / Principal) --}}
    @if($showPolicyModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm" x-data x-trap="true">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-lg w-full p-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-150 dark:border-gray-700 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 font-bold flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-gray-900 dark:text-white">Fee Void Security & Policy</h3>
                            <p class="text-xs text-gray-500">Enable or restrict payment cancellation permissions</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closePolicyModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="space-y-4 text-xs">
                    {{-- Toggle 1: Master Enable/Disable --}}
                    <label class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 cursor-pointer">
                        <input type="checkbox" wire:model="policyVoidEnabled" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white block">Enable Fee Payment Voiding Feature</span>
                            <span class="text-gray-500 block text-[11px]">When disabled, no staff member or administrator can void payment receipts across the institution.</span>
                        </div>
                    </label>

                    {{-- Toggle 2: Allow Cashier Requests --}}
                    <label class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 cursor-pointer">
                        <input type="checkbox" wire:model="policyAllowRequests" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white block">Allow Cashier Void Request Queue (Maker-Checker)</span>
                            <span class="text-gray-500 block text-[11px]">Allows regular clerks to submit requests to the Principal's approval queue without needing immediate supervisor walk-up.</span>
                        </div>
                    </label>

                    {{-- Toggle 3: Require Administrator PIN/Password --}}
                    <label class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 cursor-pointer">
                        <input type="checkbox" wire:model="policyRequirePin" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white block">Strictly Require Password / PIN for Every Void</span>
                            <span class="text-gray-500 block text-[11px]">Demands re-authenticating with the administrator's password before any financial reversal is committed to the database.</span>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-150 dark:border-gray-700 mt-5">
                    <button type="button" wire:click="closePolicyModal" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl">
                        Cancel
                    </button>
                    <button type="button" wire:click="savePolicy" class="px-5 py-2 text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md">
                        Save Policy Settings
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Day-End Cashier Closing Modal --}}
    @if($showDayEndModal && $dayEndSummary)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-2xl w-full overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-150 dark:border-gray-700 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 font-bold flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Day-End Cashier Closing Summary</h3>
                                <p class="text-xs text-gray-500">Daily reconciliation & cash drawer count for {{ date('d F, Y', strtotime($dayEndDate)) }}</p>
                            </div>
                        </div>
                        <input type="date" wire:model.live="dayEndDate" class="text-xs rounded-xl border border-gray-300 dark:border-gray-600 py-1.5 px-3 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-white font-bold">
                    </div>

                    {{-- Metric Cards --}}
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="p-4 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40">
                            <span class="text-[10px] font-bold text-emerald-600 uppercase block">Total Net Collected</span>
                            <span class="text-xl font-black text-emerald-700 dark:text-emerald-300">Rs. {{ number_format($dayEndSummary['totalCollected'], 2) }}</span>
                            <span class="text-[10px] text-gray-400 block mt-0.5">{{ $dayEndSummary['validCount'] }} receipts issued</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40">
                            <span class="text-[10px] font-bold text-blue-600 uppercase block">Cash in Hand</span>
                            <span class="text-xl font-black text-blue-700 dark:text-blue-300">Rs. {{ number_format($dayEndSummary['cashCollected'], 2) }}</span>
                            <span class="text-[10px] text-gray-400 block mt-0.5">Physical Cash</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-purple-50/60 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40">
                            <span class="text-[10px] font-bold text-purple-600 uppercase block">Bank / Online</span>
                            <span class="text-xl font-black text-purple-700 dark:text-purple-300">Rs. {{ number_format($dayEndSummary['bankCollected'], 2) }}</span>
                            <span class="text-[10px] text-gray-400 block mt-0.5">Direct Deposits</span>
                        </div>
                    </div>

                    {{-- Staff Breakdown --}}
                    @if(!empty($dayEndSummary['byCashier']))
                        <div class="mb-5">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">Collection by Staff Member</span>
                            <div class="space-y-1.5">
                                @foreach($dayEndSummary['byCashier'] as $staffName => $staffData)
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/40 text-xs">
                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $staffName }}</span>
                                        <div class="text-right">
                                            <span class="font-black text-emerald-600">Rs. {{ number_format($staffData['total'], 2) }}</span>
                                            <span class="text-[10px] text-gray-400 ml-1">({{ $staffData['count'] }} tx)</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-150 dark:border-gray-700">
                        <button type="button" wire:click="closeDayEndModal" class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                            Close
                        </button>
                        <button type="button" onclick="window.print()" class="px-5 py-2 text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span>Print Day-End Closing Sheet</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Embedded RecordPayment Modal --}}
    <livewire:admin.fee.record-payment />
</div>
