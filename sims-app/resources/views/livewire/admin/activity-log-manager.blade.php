<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-violet-500/20 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Activity Logger & Audit Trail</h1>
                <p class="text-xs text-gray-500 font-medium mt-0.5">
                    Transparent accountability records of all data modifications and system events to eliminate blames.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button
                wire:click="exportCsv"
                class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all shadow-xs"
                title="Download filtered activity logs as CSV"
            >
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export CSV</span>
            </button>

            @if(auth()->user()->hasRole('Super Admin'))
                <button
                    wire:click="purgeOldLogs(90)"
                    wire:confirm="Are you sure you want to purge audit logs older than 90 days? This action is irreversible."
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition-all shadow-xs"
                    title="Purge logs older than 90 days"
                >
                    <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Purge (&gt;90d)</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session()->has('message'))
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('message') }}</span>
            </div>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Total Audits --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Audits</p>
                <h3 class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($totalLogsCount) }}</h3>
            </div>
        </div>

        {{-- Today's Logs --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Today's Logs</p>
                <h3 class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($todayLogsCount) }}</h3>
            </div>
        </div>

        {{-- Critical / Overrides --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Critical / Overrides</p>
                <h3 class="text-2xl font-black text-amber-600 mt-0.5">{{ number_format($criticalActionsCount) }}</h3>
            </div>
        </div>

        {{-- Active Staff Today --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Staff Today</p>
                <h3 class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($uniqueUsersToday) }}</h3>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3">
            {{-- Search Bar --}}
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Search Logs</span>
                </label>
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search description, user, IP, entity..."
                        class="w-full text-xs rounded-xl border border-gray-200 pl-10 pr-9 py-2.5 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-gray-800 placeholder:text-gray-400 bg-white"
                    >
                    @if($search)
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                            title="Clear search"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Action Filter --}}
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <span>Action</span>
                </label>
                <select wire:model.live="actionFilter" class="w-full text-xs rounded-xl border border-gray-200 py-2.5 px-3 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-gray-800 bg-white">
                    <option value="">All Actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="saved">Marked / Saved</option>
                    <option value="printed">Printed / Exported</option>
                    <option value="deleted">Deleted</option>
                    <option value="override">Admin Override</option>
                    <option value="login">User Login</option>
                    <option value="logout">User Logout</option>
                    <option value="security">Security Event</option>
                </select>
            </div>

            {{-- Module Filter --}}
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Module</span>
                </label>
                <select wire:model.live="moduleFilter" class="w-full text-xs rounded-xl border border-gray-200 py-2.5 px-3 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-gray-800 bg-white">
                    <option value="">All Modules</option>
                    <option value="attendance">Attendance (Student & Teacher)</option>
                    <option value="schedule">Schedule & Timetable</option>
                    <option value="substitutions">Substitutions & Arrangements</option>
                    <option value="access_control">Feature Sharing & Access Control</option>
                    <option value="students">Students</option>
                    <option value="fees">Fees</option>
                    <option value="grades">Grades & Exams</option>
                    <option value="academics">Academics (Class/Subject)</option>
                    <option value="settings">System Settings</option>
                    <option value="users">User Accounts</option>
                    <option value="auth">Authentication</option>
                </select>
            </div>

            {{-- User Filter --}}
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Performed By</span>
                </label>
                <select wire:model.live="userFilter" class="w-full text-xs rounded-xl border border-gray-200 py-2.5 px-3 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-gray-800 bg-white">
                    <option value="">All Staff / Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range Preset --}}
            <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Date Range</span>
                </label>
                <select wire:model.live="dateFilter" class="w-full text-xs rounded-xl border border-gray-200 py-2.5 px-3 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-gray-800 bg-white">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="7days">Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="custom">Custom Date Range</option>
                </select>
            </div>
        </div>

        {{-- Custom Date Pickers (if custom selected) --}}
        @if($dateFilter === 'custom')
            <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2 text-xs text-gray-600 font-medium">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>From:</span>
                    <input type="date" wire:model.live="dateFrom" class="text-xs rounded-xl border border-gray-200 py-1.5 px-3 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-gray-800 bg-white">
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600 font-medium">
                    <span>To:</span>
                    <input type="date" wire:model.live="dateTo" class="text-xs rounded-xl border border-gray-200 py-1.5 px-3 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 text-gray-800 bg-white">
                </div>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 text-xs text-violet-600 hover:text-violet-800 font-semibold transition-colors"
                >
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Reset Date Filter</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Activity Logs Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/75">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Timestamp</span>
                            </span>
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Staff / User</span>
                            </span>
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                <span>Action & Module</span>
                            </span>
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Target Entity & Description</span>
                            </span>
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>Origin / IP</span>
                            </span>
                        </th>
                        <th class="px-5 py-3.5 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">
                            <span class="inline-flex items-center gap-1.5 justify-end">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Audit Details</span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($logs as $log)
                        @php
                            $actionColor = $log->action_color;
                            $hasDiff = !empty($log->old_values) || !empty($log->new_values);
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            {{-- Timestamp --}}
                            <td class="px-5 py-3.5 text-xs whitespace-nowrap">
                                <div class="font-bold text-gray-800">{{ $log->created_at->format('d M, Y') }}</div>
                                <div class="text-[11px] text-gray-500 font-medium flex items-center gap-1 mt-0.5">
                                    <span>{{ $log->created_at->format('h:i:s A') }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-gray-400 font-normal">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                            </td>

                            {{-- Staff / User --}}
                            <td class="px-5 py-3.5 text-xs whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 min-w-[36px] min-h-[36px] rounded-xl bg-gradient-to-br from-violet-100 to-indigo-100 border border-violet-200/60 text-violet-700 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($log->user_name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900">{{ $log->user_name ?? 'System' }}</div>
                                        <div class="text-[11px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                {{ $log->user_role ?? 'System' }}
                                            </span>
                                            @if($log->user_email)
                                                <span class="text-gray-400 truncate max-w-[140px]">{{ $log->user_email }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Action & Module --}}
                            <td class="px-5 py-3.5 text-xs whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <div>
                                        @if($log->action === 'created')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @elseif($log->action === 'updated')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                <svg class="w-3 h-3 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @elseif($log->action === 'saved')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                <svg class="w-3 h-3 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @elseif($log->action === 'printed')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <svg class="w-3 h-3 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @elseif($log->action === 'deleted')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <svg class="w-3 h-3 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @elseif($log->action === 'override')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-orange-100 text-orange-900 border border-orange-300 shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @elseif($log->action === 'login')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                <svg class="w-3 h-3 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @elseif($log->action === 'logout')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                <svg class="w-3 h-3 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                <svg class="w-3 h-3 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                <span>{{ $log->action_label }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">
                                        {{ $log->module }}
                                    </div>
                                </div>
                            </td>

                            {{-- Target Entity & Description --}}
                            <td class="px-5 py-3.5 text-xs">
                                @if($log->subject_label)
                                    <div class="font-bold text-gray-900 mb-0.5">
                                        {{ $log->subject_label }}
                                    </div>
                                @endif
                                <div class="text-gray-700 line-clamp-2">
                                    {{ $log->description }}
                                </div>
                                @if(!empty($log->changed_fields))
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                        <span class="text-[10px] text-gray-400 font-bold uppercase">Changed:</span>
                                        @foreach((array)$log->changed_fields as $field)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                {{ $field }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            {{-- Origin / IP --}}
                            <td class="px-5 py-3.5 text-xs whitespace-nowrap text-gray-500">
                                <div class="font-mono text-gray-800 font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span>{{ $log->ip_address ?: 'Local / CLI' }}</span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-1 flex items-center gap-1.5">
                                    @php
                                        $methodColor = match(strtoupper($log->method ?? 'GET')) {
                                            'POST' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'PUT', 'PATCH' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'DELETE' => 'bg-red-50 text-red-700 border-red-200',
                                            default => 'bg-gray-50 text-gray-600 border-gray-200'
                                        };
                                    @endphp
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold uppercase border {{ $methodColor }}">
                                        {{ $log->method ?: 'N/A' }}
                                    </span>
                                    @if($log->shift_type)
                                        <span class="text-gray-300">•</span>
                                        <span class="capitalize font-medium text-gray-500">{{ $log->shift_type }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Audit Details Button --}}
                            <td class="px-5 py-3.5 text-xs whitespace-nowrap text-right">
                                @if($hasDiff)
                                    <button
                                        wire:click="viewDiff({{ $log->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-violet-700 bg-violet-50 hover:bg-violet-100 border border-violet-200/80 transition-all shadow-2xs group"
                                    >
                                        <svg class="w-3.5 h-3.5 text-violet-600 group-hover:scale-110 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>View Changes</span>
                                    </button>
                                @else
                                    <span class="text-gray-400 text-xs italic">No data diff</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-gray-500">
                                <div class="w-14 h-14 rounded-2xl bg-violet-50 text-violet-500 flex items-center justify-center mx-auto mb-3 shadow-xs">
                                    <svg class="w-7 h-7 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">No Activity Logs Found</h3>
                                <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                                    There are no activity logs matching your current filter criteria. Try adjusting your search query or date range.
                                </p>
                                @if($search || $actionFilter || $moduleFilter || $userFilter || $dateFilter !== 'all')
                                    <div class="mt-4">
                                        <button
                                            type="button"
                                            wire:click="resetFilters"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-violet-700 bg-violet-50 hover:bg-violet-100 rounded-xl border border-violet-200 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Clear All Filters</span>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Detail & Visual Diff Modal --}}
    @if($showDiffModal && $activeLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4 animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-gray-200 overflow-hidden flex flex-col max-h-[90vh]">
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50/75">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 min-w-[40px] min-h-[40px] rounded-xl bg-violet-600 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                            <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-gray-900">
                                    Audit Log Details: {{ $activeLog->action_label }}
                                </h3>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-violet-100 text-violet-800 border border-violet-200">
                                    #{{ $activeLog->id }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 font-medium mt-0.5">
                                Performed on {{ $activeLog->created_at->format('M d, Y • h:i:s A') }} by <span class="font-bold text-gray-700">{{ $activeLog->user_name ?? 'System' }}</span>
                            </p>
                        </div>
                    </div>
                    <button
                        wire:click="closeDiff"
                        class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-colors"
                        title="Close modal"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 overflow-y-auto space-y-5">
                    {{-- Target Summary Card --}}
                    <div class="p-4 rounded-xl bg-gray-50/80 border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>Affected Target Entity</span>
                            </div>
                            <div class="text-sm font-bold text-gray-900 mt-1">{{ $activeLog->subject_label ?: 'System Resource' }}</div>
                            <div class="text-xs text-gray-600 mt-0.5">{{ $activeLog->description }}</div>
                        </div>
                        <div class="shrink-0 flex sm:flex-col items-start sm:items-end gap-1.5">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-violet-100 text-violet-800 border border-violet-200">
                                Module: {{ ucfirst($activeLog->module) }}
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-medium bg-gray-200/80 text-gray-700">
                                IP: {{ $activeLog->ip_address ?: 'Local' }}
                            </span>
                        </div>
                    </div>

                    {{-- Before vs After Diff Table --}}
                    @php
                        $diff = $activeLog->formatted_diff;
                    @endphp

                    @if(!empty($diff))
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5 flex items-center gap-2">
                                <svg class="w-4 h-4 text-violet-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span>Field Modifications (Before vs After)</span>
                            </h4>

                            <div class="rounded-xl border border-gray-200 overflow-hidden shadow-2xs">
                                <table class="min-w-full divide-y divide-gray-200 text-xs table-fixed">
                                    <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-[10px] tracking-wider">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left w-1/4">Field Name</th>
                                            <th class="px-4 py-2.5 text-left w-[37.5%] text-red-700 bg-red-50/50">
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="w-4 h-4 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold text-[10px]">−</span>
                                                    <span>Old Value (Before)</span>
                                                </span>
                                            </th>
                                            <th class="px-4 py-2.5 text-left w-[37.5%] text-emerald-700 bg-emerald-50/50">
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px]">+</span>
                                                    <span>New Value (After)</span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($diff as $field => $values)
                                            @php
                                                $oldStr = is_array($values['old']) ? json_encode($values['old'], JSON_PRETTY_PRINT) : ($values['old'] ?? '—');
                                                $newStr = is_array($values['new']) ? json_encode($values['new'], JSON_PRETTY_PRINT) : ($values['new'] ?? '—');
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-3 font-mono font-bold text-gray-800 bg-gray-50/50 break-words">
                                                    {{ $field }}
                                                </td>
                                                <td class="px-4 py-3 font-mono text-red-800 bg-red-50/30 line-through break-words">
                                                    {{ $oldStr !== '' ? $oldStr : '(empty)' }}
                                                </td>
                                                <td class="px-4 py-3 font-mono font-bold text-emerald-800 bg-emerald-50/40 break-words">
                                                    {{ $newStr !== '' ? $newStr : '(empty)' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="p-6 text-center text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-xs">
                            No discrete attribute changes recorded for this action.
                        </div>
                    @endif

                    {{-- Technical Context Card --}}
                    <div class="rounded-xl bg-gray-50/80 border border-gray-200 p-4 space-y-2 text-xs">
                        <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            <span>Technical Audit Context</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-gray-600 font-mono text-[11px]">
                            <div class="flex items-center gap-2">
                                <span class="font-sans font-bold text-gray-700">Endpoint:</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-200 text-gray-800">{{ $activeLog->method ?: 'N/A' }}</span>
                                <span class="truncate">{{ $activeLog->url ?: 'N/A' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-sans font-bold text-gray-700">Client IP:</span>
                                <span>{{ $activeLog->ip_address ?: 'Local' }}</span>
                            </div>
                        </div>
                        @if($activeLog->user_agent)
                            <div class="pt-2 border-t border-gray-200/60 text-[11px] text-gray-500 font-mono break-all">
                                <span class="font-sans font-bold text-gray-700">User Agent:</span> {{ $activeLog->user_agent }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 bg-gray-50 flex justify-end">
                    <button
                        wire:click="closeDiff"
                        class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all shadow-xs"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
