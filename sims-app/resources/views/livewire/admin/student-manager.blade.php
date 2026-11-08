<div class="space-y-6">
    @if(!$onlyModal)
    <x-slot name="header">Student Management</x-slot>
    @endif

    @if(!$onlyModal)
    @if(session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-sm font-semibold shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between text-sm font-semibold shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($this->isHistoricalSession)
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <div>
                    <span class="font-bold">🔒 Historical Academic Session (Read-Only Archive)</span>
                    <p class="text-xs text-amber-700 mt-0.5">Historical enrollments, roll numbers, and academic records are locked against changes. Data export and student biographical corrections remain fully available.</p>
                </div>
            </div>
            <span class="px-2.5 py-1 bg-amber-200 text-amber-900 font-bold text-xs rounded-lg whitespace-nowrap">Archive Mode</span>
        </div>
    @endif

    {{-- Main Responsive & Adaptive Container Card --}}
    <div class="glass-card rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        
        {{-- Header & Primary Actions Toolbar --}}
        <div class="p-4 sm:p-5 border-b border-gray-100 bg-white/80 backdrop-blur-md">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                {{-- Title & Active Session Badge --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-sm shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg sm:text-xl font-black text-gray-900 tracking-tight">Student Directory</h2>
                            @if($this->isHistoricalSession)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    🔒 Sealed Archive
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Active Session
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 font-medium">Manage student enrollments, profiles, and institutional records.</p>
                    </div>
                </div>

                {{-- Action Buttons Cluster --}}
                <div class="flex items-center gap-2 flex-wrap self-end md:self-center">
                    {{-- Export Excel Button (Branded Emerald with Spreadsheet Icon) --}}
                    <button
                        type="button"
                        wire:click="exportExcel"
                        class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl transition-all text-xs sm:text-sm font-bold flex items-center gap-1.5 shadow-sm shadow-emerald-600/25 border border-emerald-500 active:scale-95 duration-150 cursor-pointer"
                        title="Export Student Directory to Microsoft Excel (.xls)"
                    >
                        <svg class="w-4 h-4 text-emerald-100 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM13 3.5L18.5 9H13V3.5zM7 11.5h10v2H7v-2zm0 3.5h10v2H7v-2zm0 3.5h7v2H7v-2z"/>
                        </svg>
                        <span>Export Excel</span>
                    </button>

                    {{-- Export CSV Pill --}}
                    <button
                        type="button"
                        wire:click="exportCsv"
                        class="px-2.5 py-2 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 rounded-xl transition-all text-xs font-bold flex items-center gap-1 border border-slate-300 shadow-2xs active:scale-95 duration-150 cursor-pointer"
                        title="Export Raw Data to CSV (.csv)"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>CSV</span>
                    </button>

                    @can('student.create')
                    @if(!$this->isHistoricalSession)
                        <a
                            href="{{ request()->is('teacher/*') ? route('teacher.shared.students.import') : route('admin.students.import') }}"
                            class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl transition-all text-xs sm:text-sm font-bold flex items-center gap-1.5 shadow-sm shadow-indigo-600/25 border border-indigo-500 active:scale-95 duration-150 cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-indigo-100 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span>Import</span>
                        </a>
                        <button
                            type="button"
                            wire:click="openModal"
                            class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl transition-all text-xs sm:text-sm font-bold flex items-center gap-1.5 shadow-sm shadow-blue-600/25 border border-blue-500 active:scale-95 duration-150 cursor-pointer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            <span>Add Student</span>
                        </button>
                    @endif
                    @endcan

                    {{-- View Mode Toggle --}}
                    <div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-2xs">
                        <button 
                            type="button"
                            wire:click="$set('viewMode', 'grid')" 
                            class="p-1.5 rounded-lg transition-all {{ $viewMode === 'grid' ? 'bg-white shadow-xs text-blue-600 font-bold' : 'text-gray-400 hover:text-gray-700' }}"
                            title="Grid View"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('viewMode', 'list')" 
                            class="p-1.5 rounded-lg transition-all {{ $viewMode === 'list' ? 'bg-white shadow-xs text-blue-600 font-bold' : 'text-gray-400 hover:text-gray-700' }}"
                            title="List View"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Segmented Status & KPI Navigation Ribbon (Unified & Responsive) --}}
            <div class="flex items-center gap-1.5 mt-4 pt-3 border-t border-gray-100 overflow-x-auto no-scrollbar pb-1">
                {{-- Active --}}
                <button 
                    type="button"
                    wire:click="setQuickStatus('active')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0 cursor-pointer
                        {{ $filterStatus === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200/60' }}"
                >
                    <span class="w-1.5 h-1.5 rounded-full {{ $filterStatus === 'active' ? 'bg-white' : 'bg-emerald-500 animate-pulse' }}"></span>
                    <span>Active</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterStatus === 'active' ? 'bg-emerald-700 text-white' : 'bg-gray-200 text-gray-700 font-semibold' }}">
                        {{ $this->studentMetrics['active'] }}
                    </span>
                </button>

                {{-- All in Session --}}
                <button 
                    type="button"
                    wire:click="setQuickStatus('')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0 cursor-pointer
                        {{ $filterStatus === '' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200/60' }}"
                >
                    <span>All in Session</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterStatus === '' ? 'bg-blue-700 text-white' : 'bg-gray-200 text-gray-700 font-semibold' }}">
                        {{ $this->studentMetrics['total_session'] }}
                    </span>
                </button>

                {{-- Graduated --}}
                <button 
                    type="button"
                    wire:click="setQuickStatus('graduated')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0 cursor-pointer
                        {{ $filterStatus === 'graduated' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200/60' }}"
                >
                    <span>🎓 Graduated</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterStatus === 'graduated' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700 font-semibold' }}">
                        {{ $this->studentMetrics['graduated'] }}
                    </span>
                </button>

                {{-- Alumni Directory --}}
                <button 
                    type="button"
                    wire:click="setQuickStatus('alumni')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0 cursor-pointer
                        {{ $filterStatus === 'alumni' ? 'bg-purple-600 text-white shadow-xs' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200/60' }}"
                >
                    <span>🏛️ Alumni Directory</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterStatus === 'alumni' ? 'bg-purple-700 text-white' : 'bg-gray-200 text-gray-700 font-semibold' }}">
                        {{ $this->studentMetrics['alumni'] }}
                    </span>
                </button>

                {{-- SLC Issued --}}
                <button 
                    type="button"
                    wire:click="setQuickStatus('withdrawn')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0 cursor-pointer
                        {{ $filterStatus === 'withdrawn' ? 'bg-amber-600 text-white shadow-xs' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200/60' }}"
                >
                    <span>📄 SLC Issued</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterStatus === 'withdrawn' ? 'bg-amber-700 text-white' : 'bg-gray-200 text-gray-700 font-semibold' }}">
                        {{ $this->studentMetrics['slc'] }}
                    </span>
                </button>

                {{-- Struck Off --}}
                <button 
                    type="button"
                    wire:click="setQuickStatus('struck_off')"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap shrink-0 cursor-pointer
                        {{ $filterStatus === 'struck_off' ? 'bg-rose-600 text-white shadow-xs' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200/60' }}"
                >
                    Struck Off
                </button>

                {{-- Archived / Recycle Bin --}}
                <button 
                    type="button"
                    wire:click="setQuickStatus('archived')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0 cursor-pointer
                        {{ $filterStatus === 'archived' ? 'bg-rose-700 text-white shadow-xs' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-gray-200/60' }}"
                >
                    <span>🗑️ Recycle Bin</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterStatus === 'archived' ? 'bg-rose-800 text-white' : 'bg-gray-200 text-gray-700 font-semibold' }}">
                        {{ $this->studentMetrics['archived'] }}
                    </span>
                </button>
            </div>
        </div>

        {{-- Filter & Search Command Ribbon --}}
        <div class="p-4 sm:p-5 bg-gray-50/60 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                {{-- Search & Direct Selectors Group --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 min-w-0">
                    {{-- Search Input --}}
                    <div class="relative flex-1 min-w-[180px]">
                        <input 
                            wire:model.live.debounce.400ms="search" 
                            type="text" 
                            placeholder="Search by name, roll no, admission no, CNIC..." 
                            class="w-full pl-9 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none shadow-2xs transition-all" 
                        />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        @if($search)
                        <button 
                            type="button"
                            wire:click="$set('search', '')" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                            title="Clear search"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        @endif
                    </div>

                    {{-- Session Selector Dropdown --}}
                    <div class="relative w-full sm:w-56 shrink-0">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <select 
                            wire:model.live="selectedSessionId" 
                            class="w-full pl-8 pr-7 py-2 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm font-bold text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none shadow-2xs transition-all cursor-pointer truncate"
                            title="Switch Academic Session"
                        >
                            @foreach($academicSessions as $session)
                                <option value="{{ $session->id }}">
                                    {{ $session->name }} @if($session->is_active) (Current) @elseif($session->is_archived) (Archived) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Class Selector Dropdown --}}
                    <div class="relative w-full sm:w-48 shrink-0">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-indigo-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <select 
                            wire:model.live="selectedClassId" 
                            class="w-full pl-8 pr-7 py-2 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm font-bold text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none shadow-2xs transition-all cursor-pointer truncate"
                            title="Filter by Class"
                        >
                            @can('students.view-all-classes') 
                                <option value="">All Classes</option> 
                            @endcan
                            @foreach($classes as $cls) 
                                <option value="{{ $cls->id }}">
                                    {{ $cls->name }}@if($cls->shift_type && $cls->shift_type !== 'regular') ({{ ucfirst($cls->shift_type) }})@endif
                                </option> 
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Filter Drawer Toggle & Reset Button --}}
                <div class="flex items-center gap-2 self-end lg:self-auto shrink-0">
                    <button 
                        type="button"
                        wire:click="toggleFilters" 
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold transition-all cursor-pointer select-none active:scale-95 duration-150
                            {{ $showFilters || $this->activeFiltersCount > 0 ? 'bg-blue-50 border-blue-300 text-blue-700 shadow-2xs ring-1 ring-blue-200' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                        title="Toggle Additional Filter Criteria"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        <span>{{ $showFilters ? 'Hide Filters' : 'More Filters' }}</span>
                        @if($this->activeFiltersCount > 0)
                            <span class="w-4 h-4 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-bold">
                                {{ $this->activeFiltersCount }}
                            </span>
                        @endif
                        <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ $showFilters ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    @if($search || $selectedClassId || $this->activeFiltersCount > 0 || $filterStatus !== 'active')
                    <button 
                        type="button"
                        wire:click="resetFilters" 
                        class="inline-flex items-center gap-1 px-2.5 py-2 rounded-xl text-xs font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition-colors cursor-pointer active:scale-95 duration-150"
                        title="Reset all filters and search"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset</span>
                    </button>
                    @endif
                </div>
            </div>

            {{-- Collapsible Secondary Filter Card --}}
            @if($showFilters)
            <div class="mt-4 pt-4 border-t border-gray-200/60 animate-fade-in">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-white p-4 rounded-xl border border-gray-200/70 shadow-2xs">
                    {{-- 1. Sports Filter --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sports Activity</label>
                        <select wire:model.live="filterSport" class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs bg-white text-gray-700 font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Any Sport</option>
                            @foreach($sportsOptions as $s)
                                <option value="{{ $s->name }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 2. Extra Curricular --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Extra-Curricular</label>
                        <select wire:model.live="filterActivity" class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs bg-white text-gray-700 font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Any Activity</option>
                            @foreach($activityOptions as $a)
                                <option value="{{ $a->name }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Transport & Bus --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Transport Mode</label>
                        <div class="flex gap-2">
                            <select wire:model.live="filterTransport" class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs bg-white text-gray-700 font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Any Transport</option>
                                @foreach(App\Livewire\Admin\StudentManager::TRANSPORT_OPTIONS as $k => $l)
                                    <option value="{{ $k }}">{{ $l }}</option>
                                @endforeach
                            </select>
                            @if($filterTransport === 'school_bus')
                                <select wire:model.live="filterBus" class="w-full px-2 py-1.5 rounded-lg border border-gray-200 text-xs bg-white text-gray-700 font-medium focus:ring-2 focus:ring-blue-500 outline-none animate-fade-in">
                                    <option value="">Bus No</option>
                                    @foreach(App\Livewire\Admin\StudentManager::BUS_OPTIONS as $bus)
                                        <option value="{{ $bus }}">Bus {{ $bus }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>

                    {{-- 4. Sorting --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sort Records</label>
                        <div class="flex gap-2">
                            <select wire:model.live="sortBy" class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs bg-white text-gray-700 font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="roll_no">Roll Number</option>
                                <option value="name">Student Name</option>
                                <option value="admission_no">Admission No</option>
                            </select>
                            <select wire:model.live="sortDir" class="w-24 px-2 py-1.5 rounded-lg border border-gray-200 text-xs bg-white text-gray-700 font-medium focus:ring-2 focus:ring-blue-500 outline-none shrink-0">
                                <option value="asc">Asc</option>
                                <option value="desc">Desc</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        </div>

        <div class="p-4 sm:p-6">

        {{-- Bulk Actions Bar --}}
        @if(count($selectedStudentIds) > 0)
        @if($this->isHistoricalSession)
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between text-amber-800 text-sm shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span><strong>{{ count($selectedStudentIds) }} students selected.</strong> Bulk modifications, status changes, and class reassignments are sealed in historical archive mode.</span>
            </div>
            <button wire:click="$set('selectedStudentIds', [])" class="text-xs text-amber-700 hover:text-amber-900 underline font-semibold">
                Deselect All
            </button>
        </div>
        @else
        <div class="mb-6 p-5 bg-blue-50 border border-blue-100 rounded-2xl flex flex-col gap-4 transition-all duration-300">
            <div class="flex justify-between items-center pb-3 border-b border-blue-100/50">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                        {{ count($selectedStudentIds) }}
                    </div>
                    <span class="text-sm font-semibold text-blue-900">students selected for bulk actions</span>
                </div>
                <button wire:click="$set('selectedStudentIds', [])" class="text-xs text-gray-500 hover:text-gray-700 underline font-medium">
                    Deselect All
                </button>
            </div>
            
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                {{-- 1. Academic Program Assignment --}}
                <div class="flex-1 w-full flex flex-col gap-1.5 p-3 bg-white rounded-xl border border-blue-100/50">
                    <label class="text-[10px] font-bold text-blue-800 uppercase tracking-wider">Academic Program</label>
                    @if(count($this->availablePrograms) > 0)
                        <div class="flex gap-2">
                            <select wire:model="bulkProgramId" class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 text-xs focus:ring-1 focus:ring-blue-500 bg-white text-gray-700 font-semibold outline-none">
                                <option value="">Choose Academic Program...</option>
                                @foreach($this->availablePrograms as $prog)
                                    <option value="{{ $prog->id }}">{{ $prog->name }} ({{ $prog->code }})</option>
                                @endforeach
                            </select>
                            <div class="flex gap-1 shrink-0">
                                <button wire:click="bulkAssignProgram" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-sm transition-colors" title="Assign Academic Program">
                                    Assign Program
                                </button>
                            </div>
                        </div>
                    @else
                        <span class="text-[10px] text-gray-500 italic py-1">
                            No academic programs configured for this class.
                        </span>
                    @endif
                </div>

                {{-- Edit Button --}}
                @if($selectedClassId)
                <div class="w-full md:w-auto shrink-0 flex items-center justify-end">
                    <button wire:click="openBulkEditModal" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-2 w-full md:w-auto justify-center active:scale-95 duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.707-6.707zM1.354 4.854a.5.5 0 0 0-.708 0L.146 5.354a.5.5 0 0 0 0 .708l1.5 1.5a.5.5 0 0 0 .708 0l.5-.5a.5.5 0 0 0 0-.708l-1.5-1.5zM2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/></svg>
                        Edit Selected Students
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endif
        @endif

        {{-- Content --}}
        @if($viewMode === 'list')
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        @if($selectedClassId)
                        <th class="px-6 py-3 text-left w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer" />
                        </th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" wire:click="sortByField('admission_no')">
                            <div class="flex items-center gap-1">
                                Adm No
                                @if($sortBy === 'admission_no')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" wire:click="sortByField('roll_no')">
                            <div class="flex items-center gap-1">
                                Roll No
                                @if($sortBy === 'roll_no')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none" wire:click="sortByField('name')">
                            <div class="flex items-center gap-1">
                                Name
                                @if($sortBy === 'name')
                                    <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Father's Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        @if($selectedClassId)
                            <th class="px-6 py-3 text-left text-xs font-medium text-indigo-600 uppercase tracking-wider">Academic Program</th>
                        @endif
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50 transition-colors {{ in_array($student->id, $selectedStudentIds) ? 'bg-blue-50/20' : '' }}">
                            @if($selectedClassId)
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 w-10">
                                <input type="checkbox" wire:model="selectedStudentIds" value="{{ $student->id }}" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer" />
                            </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->admission_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ $student->roll_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $student->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 bg-blue-50/50">
                                <span class="px-2 py-1 rounded-md">{{ $student->class_name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="font-medium text-gray-800">{{ $student->father_name }}</div>
                                @if($student->father_cnic)
                                    <span class="text-[11px] font-mono text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100 block w-max mt-0.5" title="Father CNIC">
                                        {{ $student->father_cnic }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->phone }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($student->trashed())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Archived Profile
                                    </span>
                                @elseif($student->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active
                                    </span>
                                @elseif($student->status === 'withdrawn' || $student->status === 'slc_issued')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        SLC Issued
                                    </span>
                                @elseif($student->status === 'graduated')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        Graduated
                                    </span>
                                @elseif($student->status === 'struck_off')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Struck Off
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-50 text-gray-700 border border-gray-200">
                                        {{ ucfirst($student->status) }}
                                    </span>
                                @endif
                            </td>
                            @if($selectedClassId)
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($this->availablePrograms->count() > 1)
                                        <select
                                            wire:change="assignStudentProgram({{ $student->id }}, $event.target.value ? Number($event.target.value) : null)"
                                            class="px-2 py-1 text-xs rounded-lg border border-indigo-200 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 outline-none min-w-[140px] font-medium"
                                        >
                                            <option value="">-- No Program --</option>
                                            @foreach($this->availablePrograms as $prog)
                                                <option value="{{ $prog->id }}" {{ ($student->program_id ?? null) == $prog->id ? 'selected' : '' }}>
                                                    {{ $prog->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200" title="{{ $student->program_name }}">
                                            {{ $student->program_code ?? $student->program_name ?? 'Standard Program' }}
                                        </span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                <div class="flex gap-1.5 justify-end items-center">
                                    {{-- 360 Lifetime Portfolio / Audit --}}
                                    <button
                                        wire:click="openLifetimeHistory({{ $student->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-indigo-600 hover:text-indigo-900 p-1.5 hover:bg-indigo-50 rounded-lg transition-all transform hover:scale-105"
                                        title="360° Lifetime History & Audit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </button>

                                    {{-- Institutional Departure / Issue SLC --}}
                                    @if($student->status === 'active')
                                    <button
                                        wire:click="startIssueSlc({{ $student->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-amber-600 hover:text-amber-900 p-1.5 hover:bg-amber-50 rounded-lg transition-all transform hover:scale-105"
                                        title="Institutional Departure / Issue SLC"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </button>
                                    @elseif($student->status === 'withdrawn' || $student->status === 'slc_issued')
                                    <button
                                        wire:click="openLifetimeHistory({{ $student->id }})"
                                        class="text-amber-600 hover:text-amber-900 p-1.5 hover:bg-amber-50 rounded-lg transition-all transform hover:scale-105"
                                        title="SLC Issued - View & Reprint from Portfolio"
                                    >
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </button>
                                    @endif

                                    @if(\App\Services\LicenseStatus::isModuleEnabled('fees'))
                                    <a
                                        href="{{ route('admin.fee.ledger', $student->id) }}"
                                        class="text-emerald-600 hover:text-emerald-950 p-1.5 hover:bg-emerald-50 rounded-lg transition-colors"
                                        title="Fee Ledger"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </a>
                                    @endif
                                    <button
                                        wire:click="view({{ $student->id }})"
                                        class="text-gray-600 hover:text-gray-900 p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                                        title="View Profile"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    @can('student.edit')
                                    <button
                                        wire:click="edit({{ $student->id }})"
                                        class="text-blue-600 hover:text-blue-900 p-1.5 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Edit Student"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    @endcan
                                    @if($student->trashed())
                                        <div class="flex items-center gap-1">
                                            @can('student.edit')
                                            <button
                                                wire:click="restoreStudent({{ $student->id }})"
                                                wire:confirm="Restore {{ $student->name }} to active student status?"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors"
                                                title="Restore Student Profile"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                                Restore
                                            </button>
                                            @endcan
                                            @can('student.delete')
                                            @if(!$student->isDeparted())
                                            <button
                                                wire:click="forceDeleteStudent({{ $student->id }})"
                                                wire:confirm="Permanently purge {{ $student->name }}? If they have zero financial/academic records, this completely erases them from the database."
                                                class="p-1.5 text-gray-400 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                title="Permanently Purge Record"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                            </button>
                                            @endif
                                            @endcan
                                        </div>
                                    @else
                                        @can('student.delete')
                                        @if(!$this->isHistoricalSession && !$student->isDeparted())
                                        <button
                                            wire:click="delete({{ $student->id }})"
                                            wire:confirm="Delete {{ $student->name }}? If this is a fresh student with no records, they will be deleted completely. If they have attendance or fee history, they will be safely archived."
                                            class="text-rose-600 hover:text-rose-900 p-1.5 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                            title="Delete Student"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                        </button>
                                        @elseif($this->isHistoricalSession && !$student->isDeparted())
                                        <span class="text-gray-300 p-1.5 cursor-not-allowed" title="Enrollment locked (Historical Archive)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        </span>
                                        @endif
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 px-4 text-center">
                                <div class="w-12 h-12 mx-auto mb-2.5 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-gray-800">No students found</h4>
                                <p class="text-xs text-gray-500 mt-0.5">No student records match your current filter or session selection.</p>
                                @if($search || $selectedClassId || $this->activeFiltersCount > 0 || $filterStatus !== 'active')
                                    <button type="button" wire:click="resetFilters" class="mt-2.5 inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-bold transition-colors cursor-pointer">
                                        Reset Filters & Search
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @else
        {{-- Grid View (Adaptive 1 / 2 / 3 / 4 Columns) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
            @forelse($students as $student)
                <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border {{ in_array($student->id, $selectedStudentIds) ? 'border-blue-400 ring-2 ring-blue-500/20' : 'border-gray-200/80 hover:border-blue-200 hover:shadow-xs' }} transition-all relative overflow-hidden group flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex gap-2.5 items-center">
                                @if($selectedClassId)
                                {{-- Checkbox --}}
                                <input type="checkbox" wire:model="selectedStudentIds" value="{{ $student->id }}" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer w-4 h-4 shadow-sm shrink-0" />
                                @endif
                                
                                {{-- Avatar --}}
                                <div class="shrink-0">
                                    @if($student->profile_photo_path)
                                        <img src="{{ asset('storage/' . $student->profile_photo_path) }}" class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-xs">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center text-blue-700 text-base font-bold border border-blue-200/60 shadow-2xs">
                                            {{ substr($student->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Name & Details --}}
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-gray-900 text-sm line-clamp-1 truncate" title="{{ $student->name }}">{{ $student->name }}</h3>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="text-[11px] text-gray-500 font-medium font-mono">Roll: {{ $student->roll_no }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span class="text-[11px] text-gray-500 font-mono">Adm: {{ $student->admission_no }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 mt-1 flex-wrap">
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase tracking-wide bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $student->class_name }}
                                        </span>
                                        @if(!empty($student->program_code))
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase tracking-wide bg-indigo-50 text-indigo-700 border border-indigo-100" title="{{ $student->program_name }}">
                                            {{ $student->program_code }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- Info Grid --}}
                    <div class="space-y-2 mb-4">
                        @if($student->father_name)
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="truncate" title="{{ $student->father_name }}">{{ $student->father_name }}</span>
                            @if($student->father_cnic)
                                <span class="text-[10px] font-mono text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100 shrink-0" title="Father CNIC">{{ $student->father_cnic }}</span>
                            @endif
                        </div>
                        @endif

                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span>{{ $student->phone ?? 'No phone' }}</span>
                        </div>

                        <div class="flex items-start gap-2 text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 mt-0.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span class="truncate line-clamp-1" title="{{ $student->address }}">{{ $student->address ?? 'No address' }}</span>
                        </div>
                    </div>
                
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                         <div class="text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5 
                            {{ $student->trashed() ? 'text-rose-700' : ($student->status === 'active' ? 'text-emerald-700' : ($student->status === 'withdrawn' || $student->status === 'slc_issued' ? 'text-amber-700' : ($student->status === 'graduated' ? 'text-indigo-700' : 'text-rose-600'))) }}">
                             <span class="w-2 h-2 rounded-full {{ $student->trashed() ? 'bg-rose-500' : ($student->status === 'active' ? 'bg-emerald-500 animate-pulse' : ($student->status === 'withdrawn' || $student->status === 'slc_issued' ? 'bg-amber-500' : ($student->status === 'graduated' ? 'bg-indigo-500' : 'bg-rose-500'))) }}"></span>
                             {{ $student->trashed() ? 'Archived Profile' : ($student->status === 'withdrawn' || $student->status === 'slc_issued' ? 'SLC Issued' : ucfirst($student->status)) }}
                          </div>
                         <div class="flex gap-1.5 items-center">
                             {{-- 360 Lifetime Portfolio / Audit --}}
                             <button wire:click="openLifetimeHistory({{ $student->id }})" wire:loading.attr="disabled" class="text-gray-400 hover:text-indigo-600 p-1 rounded-lg transition-all hover:scale-110" title="360° Lifetime History & Audit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                             </button>

                             {{-- Institutional Departure / Issue SLC --}}
                             @if($student->status === 'active')
                             <button wire:click="startIssueSlc({{ $student->id }})" wire:loading.attr="disabled" class="text-gray-400 hover:text-amber-600 p-1 rounded-lg transition-all hover:scale-110" title="Institutional Departure / Issue SLC">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                             </button>
                             @elseif($student->status === 'withdrawn' || $student->status === 'slc_issued')
                             <button wire:click="openLifetimeHistory({{ $student->id }})" class="text-amber-500 hover:text-amber-700 p-1 rounded-lg transition-all hover:scale-110" title="SLC Issued - View & Reprint from Portfolio">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                             </button>
                             @endif

                             @if(\App\Services\LicenseStatus::isModuleEnabled('fees'))
                             <a href="{{ route('admin.fee.ledger', $student->id) }}" class="text-gray-400 hover:text-emerald-600 p-1 flex items-center rounded-lg transition-colors" title="Fee Ledger">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                             </a>
                             @endif
                             <button wire:click="view({{ $student->id }})" class="text-gray-400 hover:text-blue-600 p-1 rounded-lg transition-colors" title="View Bio-Data">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><rect width="18" height="18" x="3" y="3" rx="2"/></svg>
                             </button>
                             @can('student.edit')
                             <button wire:click="edit({{ $student->id }})" class="text-gray-400 hover:text-blue-600 p-1 rounded-lg transition-colors" title="Edit Student">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                             </button>
                             @endcan
                             @if($student->trashed())
                             <div class="flex items-center gap-1">
                                 @can('student.edit')
                                 <button wire:click="restoreStudent({{ $student->id }})" wire:confirm="Restore {{ $student->name }} to active student status?" class="text-emerald-600 hover:text-emerald-800 p-1 flex items-center gap-1 rounded-lg transition-colors font-bold text-xs" title="Restore Student">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                    <span>Restore</span>
                                 </button>
                                 @endcan
                                 @can('student.delete')
                                 @if(!$student->isDeparted())
                                 <button wire:click="forceDeleteStudent({{ $student->id }})" wire:confirm="Permanently purge {{ $student->name }}? If they have zero financial/academic records, this completely erases them from the database." class="text-gray-400 hover:text-rose-700 p-1 rounded-lg transition-colors cursor-pointer" title="Permanently Purge Record">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                 </button>
                                 @endif
                                 @endcan
                             </div>
                             @else
                             @can('student.delete')
                             @if(!$this->isHistoricalSession && !$student->isDeparted())
                             <button wire:click="delete({{ $student->id }})" wire:confirm="Delete {{ $student->name }}? If this is a fresh student with no records, they will be deleted completely. If they have attendance or fee history, they will be safely archived." class="text-gray-400 hover:text-rose-600 p-1 rounded-lg transition-colors cursor-pointer" title="Delete Student">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                             </button>
                             @elseif($this->isHistoricalSession && !$student->isDeparted())
                             <span class="text-gray-300 p-1 cursor-not-allowed" title="Enrollment locked (Historical Archive)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                             </span>
                             @endif
                             @endcan
                             @endif
                         </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 px-4 text-center bg-white rounded-2xl border border-gray-100">
                    <div class="w-12 h-12 mx-auto mb-2.5 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h4 class="text-sm font-bold text-gray-800">No students found</h4>
                    <p class="text-xs text-gray-500 mt-0.5">No student records match your current filter or session selection.</p>
                    @if($search || $selectedClassId || $this->activeFiltersCount > 0 || $filterStatus !== 'active')
                        <button type="button" wire:click="resetFilters" class="mt-2.5 inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-bold transition-colors cursor-pointer">
                            Reset Filters & Search
                        </button>
                    @endif
                </div>
            @endforelse
        </div>
        @endif
        <div class="mt-4">
            {{ $students->links() }}
        </div>
        </div>
    </div>
    @endif

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center p-4 text-center sm:items-center sm:p-0">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>

                {{-- Modal Panel --}}
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                    <div class="bg-white px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">{{ $isEditing ? 'Edit Student' : ($isReadmission ? 'Re-Admit Alumni Student' : 'Add New Student') }}</h3>
                            @if($isReadmission)
                                <p class="text-xs text-amber-700 mt-0.5 font-medium">Re-admission mode active: enrolling student into active session while preserving historical portfolio.</p>
                            @endif
                        </div>
                        <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors p-1 hover:bg-gray-100 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>

                    <form wire:submit="save">
                        <div class="px-6 py-5 max-h-[65vh] overflow-y-auto space-y-6">
                            {{-- 1. PROFILE PHOTO SECTION --}}
                            <div class="flex flex-col items-center justify-center p-4 bg-gray-50/50 rounded-2xl border border-gray-100">
                                <div class="relative group">
                                    @if ($photo) 
                                        <img src="{{ $photo->temporaryUrl() }}" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
                                    @elseif ($isEditing && \App\Models\Student::find($editingStudentId)->profile_photo_path)
                                        <img src="{{ asset('storage/' . \App\Models\Student::find($editingStudentId)->profile_photo_path) }}" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
                                    @else
                                        <div class="w-24 h-24 rounded-full bg-blue-50 flex items-center justify-center border-4 border-white shadow-md text-blue-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                        </div>
                                    @endif
                                    
                                    {{-- Overlay Button --}}
                                    <label for="photoInput" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                        <span class="text-white text-xs font-bold bg-black/50 px-2.5 py-1 rounded-md">Upload</span>
                                    </label>
                                </div>
                                
                                {{-- Actual Input --}}
                                <input wire:model="photo" id="photoInput" type="file" class="hidden" accept="image/*">

                                <div class="mt-2 text-center">
                                    <label for="photoInput" class="text-xs text-blue-600 font-semibold cursor-pointer hover:underline">
                                        {{ $isEditing && \App\Models\Student::find($editingStudentId)->profile_photo_path ? 'Change Photo' : 'Upload Photo' }}
                                    </label>
                                    @error('photo') <span class="text-red-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- 2. PERSONAL DETAILS --}}
                            <div>
                                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                                    <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </span>
                                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Personal Information</h4>
                                </div>
                                
                                @if($isActiveStudentDuplicate && !empty($activeStudentDetails))
                                    <div class="mb-4 p-4 bg-gradient-to-br from-rose-50 via-red-50/40 to-white border border-rose-200 rounded-2xl shadow-xs relative overflow-hidden">
                                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 to-red-500"></div>
                                        <div class="flex items-start justify-between gap-3 flex-wrap sm:flex-nowrap">
                                            <div class="flex items-start gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 border border-rose-200/80 flex items-center justify-center shrink-0 shadow-2xs mt-0.5">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <h5 class="text-xs font-black text-rose-950 uppercase tracking-wider">Student Is Already Actively Enrolled</h5>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-extrabold rounded-full uppercase tracking-wider border border-rose-200">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                                                            Active in School
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-800 mt-1.5 leading-relaxed">
                                                        <strong class="text-slate-900 font-bold">{{ $activeStudentDetails['name'] }}</strong> (Admission #<strong class="text-slate-900 font-bold">{{ $activeStudentDetails['admission_no'] }}</strong>) is currently enrolled in 
                                                        <span class="inline-block px-2 py-0.5 rounded-md bg-white border border-rose-200 font-bold text-rose-900 shadow-2xs">{{ $activeStudentDetails['class_name'] }}</span> 
                                                        (Roll #<strong class="text-slate-900 font-bold">{{ $activeStudentDetails['roll_no'] }}</strong>, {{ $activeStudentDetails['shift_name'] }} Shift, {{ $activeStudentDetails['session_name'] }}).
                                                    </p>
                                                    <p class="text-[11px] text-rose-700 font-semibold mt-1.5 flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                        <span>Duplicate admission is disallowed. Actively enrolled students cannot be re-admitted or added again.</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0 mt-2 sm:mt-0">
                                                <button 
                                                    type="button"
                                                    wire:click="openLifetimeHistory({{ $activeStudentDetails['id'] }})" 
                                                    class="px-3 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
                                                    title="Open 360° Lifetime Portfolio"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                    View Profile
                                                </button>
                                                <button 
                                                    type="button" 
                                                    wire:click="clearExistingStudentRecordLock(false)" 
                                                    class="px-3 py-1.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl shadow-2xs transition-all whitespace-nowrap cursor-pointer active:scale-95"
                                                    title="Clear locked student record to enter a different B-Form"
                                                >
                                                    Clear / New B-Form
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($isExistingStudentRecord && $isReadmission)
                                    <div class="mb-4 p-4 bg-gradient-to-r from-amber-500/10 via-amber-50 to-orange-500/10 border border-amber-300 rounded-2xl shadow-xs">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h5 class="text-xs font-bold text-amber-950 uppercase tracking-wide">Institutional Alumni Record Found & Locked</h5>
                                                        <span class="px-2 py-0.5 bg-amber-200/80 text-amber-900 text-[10px] font-bold rounded-full">Integrity Protected</span>
                                                    </div>
                                                    <p class="text-xs text-amber-900/90 mt-0.5 leading-relaxed">
                                                        {{ $existingStudentMessage ?: 'This B-Form is registered to a departed student. Core demographic fields are locked to preserve institutional consistency and transcript integrity.' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button 
                                                type="button" 
                                                wire:click="clearExistingStudentRecordLock(false)" 
                                                class="px-2.5 py-1 text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-lg transition-colors whitespace-nowrap shrink-0"
                                                title="Clear locked student record to enter a different student"
                                            >
                                                Clear / New B-Form
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Student NADRA B-Form / Child Registration Certificate (CRC) --}}
                                    <div class="md:col-span-2" x-data="{
                                        formatBForm(e) {
                                            let v = e.target.value.replace(/\D/g, '').slice(0, 13);
                                            let res = v;
                                            if (v.length > 5) res = v.slice(0, 5) + '-' + v.slice(5);
                                            if (v.length > 12) res = res.slice(0, 13) + '-' + res.slice(13, 14);
                                            e.target.value = res;
                                            $wire.set('b_form_number', res);
                                        }
                                    }">
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-semibold text-gray-600">
                                                Student B-Form / CRC Number <span class="text-gray-400 font-normal">(13 digits) <span class="text-red-500">*</span> </span>
                                            </label>
                                            @if($isActiveStudentDuplicate)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-800 bg-rose-100 px-2 py-0.5 rounded-md border border-rose-300">
                                                    <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    Active Student (Duplicate Disallowed)
                                                </span>
                                            @elseif($isExistingStudentRecord)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-300">
                                                    <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                    Alumni Record Locked
                                                </span>
                                            @endif
                                        </div>
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                required
                                                wire:model.live.debounce.300ms="b_form_number"
                                                @input="formatBForm($event)"
                                                placeholder="XXXXX-XXXXXXX-X"
                                                maxlength="15"
                                                class="w-full px-3.5 py-2 text-sm font-mono rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all {{ $isActiveStudentDuplicate ? 'bg-rose-50/50 border-rose-400 focus:border-rose-500 focus:ring-rose-500/20' : ($isExistingStudentRecord ? 'bg-amber-50/50 border-amber-300' : '') }}"
                                            />
                                            @if($isExistingStudentRecord)
                                                <button 
                                                    type="button" 
                                                    wire:click="clearExistingStudentRecordLock(false)" 
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-[11px] font-semibold {{ $isActiveStudentDuplicate ? 'text-rose-700 hover:text-rose-900 bg-rose-100 hover:bg-rose-200 border-rose-300' : 'text-amber-700 hover:text-amber-900 bg-amber-100 hover:bg-amber-200 border-amber-200' }} px-2 py-1 rounded-lg border transition-colors"
                                                >
                                                    Change
                                                </button>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">NADRA 13-digit Child Registration Certificate (CRC) / B-Form. Primary unique student identifier.</p>
                                        @error('b_form_number') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                                        <input wire:model="name" type="text" required @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" placeholder="Student Full Name" />
                                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Father's Name <span class="text-red-500">*</span></label>
                                        <input wire:model="father_name" type="text" required @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" placeholder="Father's Full Name" />
                                        @error('father_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Gender <span class="text-red-500">*</span></label>
                                        <select wire:model="gender" required @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}">
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        @error('gender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Date of Birth <span class="text-red-500">*</span> </label>
                                        <input wire:model="dob" type="date" required @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" />
                                        @error('dob') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- 2.5 FAMILY & GUARDIAN CNIC SECTION --}}
                            <div>
                                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                                    <span class="p-1.5 bg-purple-50 text-purple-600 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </span>
                                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Family Unit & NADRA CNIC</h4>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Father CNIC with Auto-Hyphenation --}}
                                    <div x-data="{
                                        formatCNIC(e) {
                                            let v = e.target.value.replace(/\D/g, '').slice(0, 13);
                                            let res = v;
                                            if (v.length > 5) res = v.slice(0, 5) + '-' + v.slice(5);
                                            if (v.length > 12) res = res.slice(0, 13) + '-' + res.slice(13, 14);
                                            e.target.value = res;
                                            $wire.set('father_cnic', res);
                                        }
                                    }">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                                            Father's CNIC <span class="text-gray-400 font-normal">(13 digits) <span class="text-red-500">*</span> </span>
                                        </label>
                                        <input 
                                            type="text" 
                                            required
                                            @disabled($isExistingStudentRecord)
                                            wire:model.live.debounce.300ms="father_cnic"
                                            @input="formatCNIC($event)"
                                            placeholder="XXXXX-XXXXXXX-X"
                                            maxlength="15"
                                            class="w-full px-3.5 py-2 text-sm font-mono rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}"
                                        />
                                        @error('father_cnic') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Sibling Concession Policy (Gated by Fees License) --}}
                                    @if(\App\Services\LicenseStatus::isModuleEnabled('fees'))
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Sibling Fee Policy</label>
                                        <select wire:model.live="sibling_concession_status" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all bg-white font-medium">
                                            <option value="none">Standard Fee (No Family Concession)</option>
                                            <option value="policy_eligible">Policy Eligible (Auto Sibling Discount)</option>
                                            <option value="custom_waiver">Custom Approved Waiver (Manual Amount)</option>
                                            <option value="disallowed">Strictly Disallowed (Barred from Concession)</option>
                                        </select>
                                        @error('sibling_concession_status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Custom Concession Amount Input --}}
                                    @if($sibling_concession_status === 'custom_waiver')
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Custom Approved Concession (Rs.)</label>
                                            <input wire:model="custom_concession_amount" type="number" step="0.01" placeholder="e.g. 500" class="w-full px-3.5 py-2 text-sm rounded-xl border border-amber-300 bg-amber-50/30 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all" />
                                            @error('custom_concession_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                    @endif
                                </div>

                                {{-- Live Sibling Detection Card --}}
                                @if(!empty($detectedSiblings))
                                    <div class="mt-3 p-3 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl">
                                        <div class="flex items-center gap-2 text-blue-900 font-bold text-xs mb-1">
                                            <span>👨‍👩‍👧‍👦 Family Unit Detected</span>
                                            <span class="px-2 py-0.5 bg-blue-200 text-blue-800 text-[10px] rounded-full font-mono">{{ count($detectedSiblings) }} Sibling(s) Enrolled</span>
                                        </div>
                                        <p class="text-[11px] text-blue-700 mb-2">The following active siblings share this Father's CNIC:</p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($detectedSiblings as $sibling)
                                                <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-blue-100 shadow-xs text-xs">
                                                    <div>
                                                        <span class="font-bold text-gray-800">{{ $sibling['name'] }}</span>
                                                        <span class="text-gray-400 text-[10px] block">Roll #{{ $sibling['roll_no'] }} • {{ $sibling['class_name'] }}</span>
                                                    </div>
                                                    <span class="text-[10px] text-indigo-600 font-semibold bg-indigo-50 px-2 py-1 rounded">
                                                        Adm: {{ $sibling['admission_date'] }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Institutional Alumni / Returning Student Alert Card (Departed Students Only) --}}
                                @if(!$isActiveStudentDuplicate && !empty($detectedAlumni))
                                    <div class="mt-4 p-4 bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-amber-500/10 border border-amber-300 rounded-2xl shadow-sm backdrop-blur-sm relative overflow-hidden transition-all">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0 ring-1 ring-amber-500/30">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h5 class="text-sm font-bold text-amber-950">Institutional Alumni Record Detected</h5>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-500/15 text-amber-800 text-[10px] font-bold rounded-full uppercase tracking-wider border border-amber-400/30">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                            {{ $detectedAlumni['status'] }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-amber-900/90 mt-0.5 leading-relaxed">
                                                        <strong>{{ $detectedAlumni['name'] }}</strong> (Admission #<strong>{{ $detectedAlumni['admission_no'] }}</strong>) is previously registered. Historical records and transcripts remain safely preserved.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3.5 pt-3 border-t border-amber-200/60 flex items-center justify-between flex-wrap gap-2">
                                            <span class="text-[11px] text-amber-700 font-medium">Original Admission: {{ $detectedAlumni['original_admission_date'] ?? '-' }}</span>
                                            @if($isReadmission)
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-100/90 px-3 py-1.5 rounded-xl border border-emerald-300 shadow-sm">
                                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                        Re-Admission Mode Active
                                                    </span>
                                                    <button 
                                                        type="button" 
                                                        wire:click="cancelReadmission" 
                                                        class="text-xs text-rose-600 hover:text-rose-800 font-semibold hover:underline px-2 py-1"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            @else
                                                <button 
                                                    type="button" 
                                                    wire:click="initiateReadmit({{ $detectedAlumni['id'] }})"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all hover:shadow-md transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    <span wire:loading.remove wire:target="initiateReadmit({{ $detectedAlumni['id'] }})">Re-Admit to Active Session</span>
                                                    <span wire:loading wire:target="initiateReadmit({{ $detectedAlumni['id'] }})">Loading Profile...</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Expandable Guardian Options --}}
                                <div class="mt-3">
                                    <label class="flex items-center gap-2 cursor-pointer select-none text-xs font-semibold text-gray-600">
                                        <input type="checkbox" wire:model.live="is_guardian_primary" @disabled($isExistingStudentRecord) class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 {{ $isExistingStudentRecord ? 'cursor-not-allowed' : '' }}">
                                        <span>Guardian is Primary Contact (or Father Deceased/Abroad)</span>
                                    </label>

                                    @if($is_guardian_primary)
                                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4 p-3 bg-gray-50 border border-gray-200 rounded-xl">
                                            <div x-data="{
                                                formatGuardianCNIC(e) {
                                                    let v = e.target.value.replace(/\D/g, '').slice(0, 13);
                                                    let res = v;
                                                    if (v.length > 5) res = v.slice(0, 5) + '-' + v.slice(5);
                                                    if (v.length > 12) res = res.slice(0, 13) + '-' + res.slice(13, 14);
                                                    e.target.value = res;
                                                    $wire.set('guardian_cnic', res);
                                                }
                                            }">
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">Guardian's CNIC</label>
                                                <input 
                                                    wire:model.live.debounce.300ms="guardian_cnic" 
                                                    @input="formatGuardianCNIC($event)"
                                                    @disabled($isExistingStudentRecord)
                                                    type="text" 
                                                    placeholder="XXXXX-XXXXXXX-X" 
                                                    maxlength="15" 
                                                    class="w-full px-3.5 py-2 text-sm font-mono rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all bg-white {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" 
                                                />
                                                @error('guardian_cnic') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">Guardian Relation</label>
                                                <input 
                                                    wire:model="guardian_relation" 
                                                    @disabled($isExistingStudentRecord)
                                                    type="text" 
                                                    placeholder="e.g. Uncle, Grandfather, Mother" 
                                                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all bg-white {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" 
                                                />
                                                @error('guardian_relation') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    @endif

                                    {{-- WhatsApp Notification Consent Toggle --}}
                                    <div class="mt-3 flex items-center justify-between p-3 bg-emerald-50/70 border border-emerald-100 rounded-xl">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                                WA
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-800 block">WhatsApp Notification Consent</span>
                                                <span class="text-[11px] text-gray-500">Dispatch automated attendance and fee notices to primary guardian</span>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" wire:model="guardian_whatsapp_opt_in" class="sr-only peer">
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. ACADEMIC DETAILS --}}
                            <div>
                                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                    </span>
                                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Academic Information</h4>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Admission No <span class="text-red-500">*</span></label>
                                        <input wire:model="admission_no" type="text" required @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" placeholder="e.g. ADM-2025-001" />
                                        @error('admission_no') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="block text-xs font-semibold text-gray-600">Roll No <span class="text-red-500">*</span></label>
                                            @if(!$isEditing)
                                            <label class="flex items-center text-[10px] text-gray-500 cursor-pointer select-none font-medium">
                                                <input type="checkbox" wire:model.live="auto_roll_no" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-1 w-3.5 h-3.5" />
                                                Auto-assign
                                            </label>
                                            @endif
                                        </div>
                                        <input wire:model="roll_no" type="text" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all disabled:bg-gray-50 disabled:text-gray-400" placeholder="e.g. 101" @if($auto_roll_no && !$isEditing) disabled @endif />
                                        @error('roll_no') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    @if($currentSessionIsRegular)
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Class <span class="text-red-500">*</span></label>
                                            <select wire:model.live="class_id" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white font-medium">
                                                <option value="">Select Class</option>
                                                @foreach($classes as $cls)
                                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('class_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                                Admission Date <span class="text-red-500">*</span>
                                            </label>
                                            <input wire:model="admission_date" type="date" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                                            @error('admission_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    @else
                                        <div class="md:col-span-2 p-4 bg-gray-50 rounded-2xl border border-gray-100 space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Student Shift Assignment</label>
                                                <div class="flex gap-4">
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" wire:model.live="student_shift_option" value="morning" class="text-blue-600 focus:ring-blue-500">
                                                        <span class="text-sm font-semibold text-gray-700">Morning Only</span>
                                                    </label>
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" wire:model.live="student_shift_option" value="evening" class="text-blue-600 focus:ring-blue-500">
                                                        <span class="text-sm font-semibold text-gray-700">Evening Only</span>
                                                    </label>
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="radio" wire:model.live="student_shift_option" value="both" class="text-blue-600 focus:ring-blue-500">
                                                        <span class="text-sm font-semibold text-gray-700">Both Shifts</span>
                                                    </label>
                                                </div>
                                                @error('student_shift_option')
                                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                @if($student_shift_option === 'morning' || $student_shift_option === 'both')
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Morning Class <span class="text-red-500">*</span></label>
                                                        <select wire:model="singleStudentClasses.morning" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white font-medium">
                                                            <option value="">Select Morning Class...</option>
                                                            @foreach($this->morningClasses as $cls)
                                                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('singleStudentClasses.morning')
                                                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                @endif

                                                @if($student_shift_option === 'evening' || $student_shift_option === 'both')
                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Evening Class <span class="text-red-500">*</span></label>
                                                        <select wire:model="singleStudentClasses.evening" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white font-medium">
                                                            <option value="">Select Evening Class...</option>
                                                            @foreach($this->eveningClasses as $cls)
                                                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('singleStudentClasses.evening')
                                                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                                Admission Date <span class="text-red-500">*</span>
                                            </label>
                                            <input wire:model="admission_date" type="date" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                                            @error('admission_date') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>

                                {{-- Academic Program Selection --}}
                                @if($class_id)
                                    <div class="mt-4 bg-slate-50 border border-slate-200 rounded-2xl p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                Academic Program <span class="text-red-500">*</span>
                                            </label>
                                            @if($this->selectedClassPrograms->count() > 1)
                                                <span class="text-[11px] font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-full">
                                                    Elective Group
                                                </span>
                                            @endif
                                        </div>
                                        <select wire:model.live="program_id" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white font-medium">
                                            <option value="">Select Academic Program...</option>
                                            @foreach($this->selectedClassPrograms as $prog)
                                                <option value="{{ $prog->id }}">{{ $prog->name }} ({{ $prog->code }})</option>
                                            @endforeach
                                        </select>
                                        @error('program_id') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror

                                        @if($this->currentSelectedProgram)
                                            <div class="mt-3 pt-3 border-t border-slate-200">
                                                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1.5">
                                                    Bundled Subjects ({{ $this->currentSelectedProgram->subjects->count() }}):
                                                </span>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($this->currentSelectedProgram->subjects as $sub)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $sub->pivot->subject_type === 'compulsory' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                            {{ $sub->name }}
                                                            @if($sub->pivot->subject_type === 'elective')
                                                                <span class="ml-1 text-[10px] text-amber-600 font-bold uppercase">(Elective)</span>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- 4. CONTACT & OTHER INFO --}}
                            <div>
                                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                                    <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </span>
                                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Contact & Address Details</h4>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Phone Number</label>
                                        <input wire:model="phone" type="tel" @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" placeholder="e.g. 03001234567" />
                                        <p class="text-[10px] text-gray-400 mt-1">Pakistani format: 03XX-XXXXXXX</p>
                                        @error('phone') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Email Address (Optional)</label>
                                        <input wire:model="email" type="email" @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" placeholder="student@example.com" />
                                        @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Transport Mode</label>
                                        <select wire:model.live="transport_mode" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white">
                                            @foreach(App\Livewire\Admin\StudentManager::TRANSPORT_OPTIONS as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('transport_mode') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    @if($transport_mode === 'school_bus')
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Select Bus</label>
                                        <select wire:model="vehicle_number" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all bg-white">
                                            <option value="">Select Bus...</option>
                                            @foreach(App\Livewire\Admin\StudentManager::BUS_OPTIONS as $bus)
                                                <option value="{{ $bus }}">{{ $bus }}</option>
                                            @endforeach
                                        </select>
                                        @error('vehicle_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    @elseif($transport_mode === 'private_van' || $transport_mode === 'car' || $transport_mode === 'bike')
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Vehicle Details / Van No.</label>
                                        <input wire:model="vehicle_number" type="text" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" placeholder="e.g. Van No. 12 or Honda City" />
                                        @error('vehicle_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    @endif

                                    @if($isEditing)
                                    <div class="p-3 bg-gray-50/80 rounded-xl border border-gray-100 flex flex-col justify-center">
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Institutional Lifecycle Status</label>
                                        <div class="flex items-center gap-2">
                                            @php
                                                $st = strtolower($status ?? 'active');
                                                $badgeClasses = match($st) {
                                                    'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                    'withdrawn', 'slc_issued' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                    'graduated' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                    'struck_off' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                    default => 'bg-gray-50 text-gray-700 border-gray-200'
                                                };
                                                $dotClasses = match($st) {
                                                    'active' => 'bg-emerald-500',
                                                    'withdrawn', 'slc_issued' => 'bg-amber-500',
                                                    'graduated' => 'bg-indigo-500',
                                                    'struck_off' => 'bg-rose-500',
                                                    default => 'bg-gray-400'
                                                };
                                                $statusLabel = match($st) {
                                                    'active' => 'Active Enrolled Student',
                                                    'withdrawn', 'slc_issued' => 'Withdrawn (SLC Issued)',
                                                    'graduated' => 'Graduated Alumni',
                                                    'struck_off' => 'Struck Off (Absence/Dues)',
                                                    default => ucfirst($st)
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $badgeClasses }}">
                                                <span class="w-2 h-2 rounded-full {{ $dotClasses }}"></span>
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">Lifecycle transitions are governed via 360° Profile & formal departure workflows.</p>
                                    </div>
                                    @endif

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Address</label>
                                        <textarea wire:model="address" @disabled($isExistingStudentRecord) class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all resize-none h-20 {{ $isExistingStudentRecord ? 'bg-gray-100 text-gray-700 cursor-not-allowed border-gray-200 shadow-none' : '' }}" placeholder="Street Address, Block, City"></textarea>
                                        @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- 5. SPORTS & ACTIVITIES --}}
                            <div>
                                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                                    <span class="p-1.5 bg-amber-50 text-amber-600 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7.468 7.468A12.324 12.324 0 0112 11.25c2.324 0 4.494.64 6.35 1.758m0 0A12.324 12.324 0 0112 14.75c-2.324 0-4.494-.64-6.35-1.758m12.7 0A12.324 12.324 0 0112 18.25c-2.324 0-4.494-.64-6.35-1.758" /></svg>
                                    </span>
                                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Talents & Activities</h4>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Sports</label>
                                        <div class="space-y-1 h-32 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-gray-50 mb-2">
                                            @foreach($sportsOptions as $sport)
                                                <div class="flex items-center justify-between hover:bg-gray-200/50 p-1.5 rounded-lg group">
                                                    @if($editingOptionId === $sport->id)
                                                        <div class="flex items-center gap-2 w-full">
                                                            <input type="text" wire:model="editingOptionName" class="flex-1 text-xs border-gray-300 rounded-lg px-2 py-1" @keydown.enter.prevent="$wire.renameOption()">
                                                            <button type="button" wire:click="renameOption" class="text-green-600 hover:text-green-700 p-1">✓</button>
                                                            <button type="button" wire:click="$set('editingOptionId', null)" class="text-gray-500 hover:text-gray-700 p-1">✕</button>
                                                        </div>
                                                    @else
                                                        <label class="flex items-center space-x-2 cursor-pointer flex-1 select-none">
                                                            <input type="checkbox" wire:model="sports" value="{{ $sport->name }}" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4">
                                                            <span class="text-xs text-gray-700 font-semibold">{{ $sport->name }}</span>
                                                        </label>
                                                        <div class="hidden group-hover:flex items-center gap-1">
                                                            <button type="button" wire:click="startEditOption({{ $sport->id }}, '{{ addslashes($sport->name) }}')" class="text-[10px] font-bold text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-2 py-0.5 rounded transition-colors">Edit</button>
                                                            <button type="button" wire:click="deleteOption({{ $sport->id }})" class="text-[10px] font-bold text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-0.5 rounded transition-colors">Delete</button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="newSportName" placeholder="Add sport..." class="flex-1 text-xs border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-3 py-1.5 outline-none" @keydown.enter.prevent="$wire.addOption('sport')">
                                            <button type="button" wire:click="addOption('sport')" class="bg-blue-600 text-white px-3 py-1.5 rounded-xl text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm shadow-blue-200">+</button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Extra-Curricular Activities</label>
                                        <div class="space-y-1 h-32 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-gray-50 mb-2">
                                            @foreach($activityOptions as $activity)
                                                <div class="flex items-center justify-between hover:bg-gray-200/50 p-1.5 rounded-lg group">
                                                    @if($editingOptionId === $activity->id)
                                                        <div class="flex items-center gap-2 w-full">
                                                            <input type="text" wire:model="editingOptionName" class="flex-1 text-xs border-gray-300 rounded-lg px-2 py-1" @keydown.enter.prevent="$wire.renameOption()">
                                                            <button type="button" wire:click="renameOption" class="text-green-600 hover:text-green-700 p-1">✓</button>
                                                            <button type="button" wire:click="$set('editingOptionId', null)" class="text-gray-500 hover:text-gray-700 p-1">✕</button>
                                                        </div>
                                                    @else
                                                        <label class="flex items-center space-x-2 cursor-pointer flex-1 select-none">
                                                            <input type="checkbox" wire:model="extra_curriculars" value="{{ $activity->name }}" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4">
                                                            <span class="text-xs text-gray-700 font-semibold">{{ $activity->name }}</span>
                                                        </label>
                                                        <div class="hidden group-hover:flex items-center gap-1">
                                                            <button type="button" wire:click="startEditOption({{ $activity->id }}, '{{ addslashes($activity->name) }}')" class="text-[10px] font-bold text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-2 py-0.5 rounded transition-colors">Edit</button>
                                                            <button type="button" wire:click="deleteOption({{ $activity->id }})" class="text-[10px] font-bold text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-0.5 rounded transition-colors">Delete</button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="newActivityName" placeholder="Add activity..." class="flex-1 text-xs border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-3 py-1.5 outline-none" @keydown.enter.prevent="$wire.addOption('activity')">
                                            <button type="button" wire:click="addOption('activity')" class="bg-blue-600 text-white px-3 py-1.5 rounded-xl text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm shadow-blue-200">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 rounded-b-2xl">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-xl transition-colors font-medium">Cancel</button>
                            @if($isActiveStudentDuplicate)
                                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-rose-50 to-red-50 text-rose-700 border border-rose-200 text-xs font-bold shadow-2xs select-none">
                                    <span class="flex h-2 w-2 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-600"></span>
                                    </span>
                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    <span>Admission Blocked (Student Already Active)</span>
                                </div>
                            @else
                                <button type="submit" class="px-5 py-2 {{ $isReadmission ? 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white rounded-xl transition-all text-sm font-semibold flex items-center gap-2 active:scale-95 duration-150 shadow-sm">
                                    <span wire:loading.remove wire:target="save">{{ $isEditing ? 'Update Student' : ($isReadmission ? 'Confirm & Re-Admit Student' : 'Add Student') }}</span>
                                    <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Saving...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    {{-- Bulk Edit Modal --}}
    @if($showBulkEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto" x-transition>
            <div class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl relative flex flex-col max-h-[90vh]" @click.away="$set('showBulkEditModal', false)">
                
                {{-- Modal Header --}}
                <div class="flex justify-between items-center p-5 border-b border-gray-100 bg-white rounded-t-2xl z-10 sticky top-0 shadow-sm">
                    <h3 class="text-lg font-extrabold text-gray-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Bulk Edit Students
                    </h3>
                    <button type="button" wire:click="$set('showBulkEditModal', false)" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 p-2 rounded-full transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    {{-- Tab Switcher --}}
                    @if(!$currentSessionIsRegular)
                        <div class="flex p-1 bg-gray-100 rounded-xl">
                            <button type="button" wire:click="$set('bulkActionType', 'status')" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-lg transition-all {{ $bulkActionType === 'status' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                                Update Status
                            </button>
                            <button type="button" wire:click="$set('bulkActionType', 'shift')" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-lg transition-all {{ $bulkActionType === 'shift' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                                Update Shift
                            </button>
                        </div>
                    @endif

                    {{-- Form Contents --}}
                    @if($bulkActionType === 'status')
                        <div class="space-y-4">
                            <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100/50">
                                <label class="block text-xs font-bold text-blue-800 uppercase tracking-wider mb-2">Select Target Status</label>
                                <select wire:model="bulkStatus" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500/20 bg-white text-gray-800 font-semibold outline-none transition-all">
                                    <option value="">Select Status...</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('bulkStatus')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Selected Students Preview --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Selected Students ({{ count($selectedStudentIds) }})</h4>
                                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                    @foreach($selectedStudentIds as $studentId)
                                        @php $st = \App\Models\Student::find($studentId); @endphp
                                        @if($st)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                                                        {{ strtoupper(substr($st->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <span class="font-bold text-gray-800 text-sm">{{ $st->name }}</span>
                                                        <span class="text-xs text-gray-400 block">Roll: {{ $st->roll_no ?? '-' }} | Adm: {{ $st->admission_no }}</span>
                                                    </div>
                                                </div>
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $st->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                    {{ ucfirst($st->status) }}
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="space-y-4">
                            {{-- Shift Options --}}
                            <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100/50 space-y-3">
                                <label class="block text-xs font-bold text-blue-800 uppercase tracking-wider">Select Target Shift</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" wire:model.live="bulkShiftOption" value="morning" class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm font-semibold text-gray-700">Morning Only</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" wire:model.live="bulkShiftOption" value="evening" class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm font-semibold text-gray-700">Evening Only</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" wire:model.live="bulkShiftOption" value="both" class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm font-semibold text-gray-700">Both Shifts</span>
                                    </label>
                                </div>
                                @error('bulkShiftOption')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Single Class Selectors for Bulk Shift Update --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-200">
                                @if($bulkShiftOption === 'morning' || $bulkShiftOption === 'both')
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Morning Class Selection</label>
                                        <select wire:model="bulkClassMorning" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 bg-white text-gray-800 font-semibold outline-none transition-all">
                                            <option value="">Select Morning Class...</option>
                                            @foreach($this->morningClasses as $cls)
                                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('bulkClassMorning')
                                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif

                                @if($bulkShiftOption === 'evening' || $bulkShiftOption === 'both')
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Evening Class Selection</label>
                                        <select wire:model="bulkClassEvening" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500/20 bg-white text-gray-800 font-semibold outline-none transition-all">
                                            <option value="">Select Evening Class...</option>
                                            @foreach($this->eveningClasses as $cls)
                                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('bulkClassEvening')
                                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif
                            </div>

                            {{-- Selected Students Preview with Current Enrollment Info --}}
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Selected Students ({{ count($selectedStudentIds) }})</h4>
                                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                    @php
                                        $bulkStudentsList = \App\Models\Student::whereIn('id', $selectedStudentIds)->get();
                                        $bulkEnrollmentsList = DB::table('enrollments')
                                            ->whereIn('enrollments.student_id', $selectedStudentIds)
                                            ->where('enrollments.academic_session_id', $selectedSessionId)
                                            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
                                            ->select('enrollments.student_id', 'enrollments.shift_type', 'classes.name as class_name')
                                            ->get()
                                            ->groupBy('student_id');
                                    @endphp
                                    @foreach($bulkStudentsList as $st)
                                        @php
                                            $stEnrs = $bulkEnrollmentsList->get($st->id, collect());
                                            $morningClass = $stEnrs->firstWhere('shift_type', 'morning')?->class_name;
                                            $eveningClass = $stEnrs->firstWhere('shift_type', 'evening')?->class_name;
                                            $hasMorning = !empty($morningClass);
                                            $hasEvening = !empty($eveningClass);
                                        @endphp
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                                                    {{ strtoupper(substr($st->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-800 text-sm">{{ $st->name }}</span>
                                                    <span class="text-xs text-gray-400 block">Roll: {{ $st->roll_no ?? '-' }} | Adm: {{ $st->admission_no }}</span>
                                                </div>
                                            </div>
                                            <div class="flex gap-1.5 items-center">
                                                @if($hasMorning)
                                                    <span class="bg-blue-100/70 text-blue-700 px-2 py-0.5 rounded font-semibold text-[10px]">Morning: {{ $morningClass }}</span>
                                                @endif
                                                @if($hasEvening)
                                                    <span class="bg-purple-100/70 text-purple-700 px-2 py-0.5 rounded font-semibold text-[10px]">Evening: {{ $eveningClass }}</span>
                                                @endif
                                                @if(!$hasMorning && !$hasEvening)
                                                    <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded font-semibold text-[10px]">No Shift</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 rounded-b-2xl">
                    <button type="button" wire:click="$set('showBulkEditModal', false)" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-xl transition-colors font-medium">Cancel</button>
                    <button type="button" wire:click="saveBulkEdit" class="px-5 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-semibold flex items-center gap-2 active:scale-95 duration-150">
                        <span wire:loading.remove wire:target="saveBulkEdit">Save Updates</span>
                        <span wire:loading wire:target="saveBulkEdit" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- View Profile Modal --}}
    @if(!$onlyModal && $showViewModal && $viewingStudent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto" x-transition>
            <div class="bg-gray-100 rounded-2xl w-full max-w-5xl shadow-2xl relative flex flex-col h-[90vh]" @click.away="$set('showViewModal', false)">
                
                {{-- Modal Header --}}
                <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-white rounded-t-2xl z-10 sticky top-0 shadow-sm">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Student Bio-Data
                    </h3>
                    <div class="flex items-center gap-3">
                         <button onclick="printDiv('print-area')" class="text-gray-500 hover:text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                            Print
                        </button>
                        <button type="button" wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 p-2 rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body (A4 Style Viewer) --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar bg-gray-100 p-8 flex justify-center">
                    
                    {{-- 'Paper' Container - Resume Style --}}
                    <div id="print-area" class="bg-white w-[210mm] min-h-[297mm] shadow-2xl mx-auto text-gray-800 relative" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;" x-data="{ showPhotoModal: false }">
                        
                        {{-- Print Styles --}}
                        <style>
                            @media print {
                                body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                                #print-area { box-shadow: none; margin: 0; width: 100%; }
                                .no-print { display: none !important; }
                            }
                            .profile-section-title { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #111; }
                            .profile-row { display: flex; margin-bottom: 8px; font-size: 13px; }
                            .profile-label { width: 120px; color: #666; font-weight: 500; }
                            .profile-value { flex: 1; color: #111; font-weight: 400; }
                        </style>

                        {{-- School Header --}}
                        <div style="background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); color: #fff; padding: 20px 30px; text-align: center;">
                            <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 3px;">{{ \App\Models\Setting::getFormalInstituteName() }}</h1>
                            <p style="font-size: 12px; margin: 0; opacity: 0.9;">{{ \App\Models\Setting::getGlobal('institute_address', 'G-6/2, Islamabad') }}</p>
                        </div>

                        {{-- Photo Lightbox Modal --}}
                        <div x-show="showPhotoModal" x-cloak @click="showPhotoModal = false" 
                             style="position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center; cursor: zoom-out;"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100">
                            @if($viewingStudent->profile_photo_path)
                                <img src="{{ asset('storage/' . $viewingStudent->profile_photo_path) }}" 
                                     style="max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                            @endif
                            <button @click="showPhotoModal = false" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: #fff; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px;">✕</button>
                        </div>

                        {{-- Two Column Layout --}}
                        <div style="display: flex;">
                            
                            {{-- LEFT SIDEBAR --}}
                            <div style="width: 35%; background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%); padding: 30px 20px; border-right: 1px solid #dee2e6;">
                                
                                {{-- Profile Photo - Clickable --}}
                                <div style="text-align: center; margin-bottom: 25px;">
                                    @if($viewingStudent->profile_photo_path)
                                        <div @click="showPhotoModal = true" style="cursor: zoom-in; position: relative; display: inline-block;">
                                            <img src="{{ asset('storage/' . $viewingStudent->profile_photo_path) }}" 
                                                 style="width: 140px; height: 170px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border-radius: 8px;">
                                            <div style="position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.6); color: #fff; padding: 3px 6px; border-radius: 4px; font-size: 9px;">
                                                🔍 Click to enlarge
                                            </div>
                                        </div>
                                    @else
                                        <div style="width: 140px; height: 170px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 56px; font-weight: 700; text-transform: uppercase; box-shadow: 0 4px 15px rgba(0,0,0,0.15); border-radius: 8px;">
                                            {{ strtoupper(substr($viewingStudent->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Contact Section --}}
                                <div style="margin-bottom: 25px;">
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/></svg>
                                        Contact
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Phone</span>
                                        <span class="profile-value">{{ $viewingStudent->phone ?? '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Email</span>
                                        <span class="profile-value">{{ $viewingStudent->email ?? '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Address</span>
                                        <span class="profile-value" style="line-height: 1.4;">{{ $viewingStudent->address ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Personal Info Section --}}
                                <div style="margin-bottom: 25px;">
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                                        Personal Info
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Father's Name</span>
                                        <span class="profile-value" style="font-weight: 600;">{{ $viewingStudent->father_name ?? '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Date of Birth</span>
                                        <span class="profile-value">{{ $viewingStudent->dob ? \Carbon\Carbon::parse($viewingStudent->dob)->format('d-m-Y') : '-' }}</span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Gender</span>
                                        <span class="profile-value" style="text-transform: capitalize;">{{ $viewingStudent->gender ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Family & Sibling Unit Section --}}
                                <div style="margin-bottom: 25px;">
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM5.5 12.5c0-.6.4-1 1-1H7v-1h-.5c-.8 0-1.5-.7-1.5-1.5V8c0-.6-.4-1-1-1H3c-.6 0-1 .4-1 1v1c0 .8-.7 1.5-1.5 1.5H0v1h.5c.6 0 1 .4 1 1v1c0 .6.4 1 1 1h1c.6 0 1-.4 1-1v-1h.5c.8 0 1.5-.7 1.5-1.5v-1c0-.6.4-1 1-1h.5v-1h-.5c-.8 0-1.5-.7-1.5-1.5V5z"/></svg>
                                        Family Unit & Sibling Profile
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Father CNIC</span>
                                        <span class="profile-value" style="font-family: monospace; font-weight: 700; color: #6b21a8;">{{ $viewingStudent->father_cnic ?? 'Not Registered' }}</span>
                                    </div>
                                    @if($viewingStudent->guardian_cnic)
                                        <div class="profile-row">
                                            <span class="profile-label">Guardian ({{ $viewingStudent->guardian_relation }})</span>
                                            <span class="profile-value" style="font-family: monospace;">{{ $viewingStudent->guardian_cnic }}</span>
                                        </div>
                                    @endif
                                    <div class="profile-row">
                                        <span class="profile-label">Sibling Order</span>
                                        <span class="profile-value">
                                            <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background-color: #f3e8ff; color: #7e22ce;">
                                                Child #{{ $viewingStudent->sibling_rank + 1 }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Active Siblings</span>
                                        <span class="profile-value">
                                            @if($viewingStudent->siblings->count() > 0)
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    @foreach($viewingStudent->siblings as $sib)
                                                        <div style="font-size: 12px; font-weight: 500; color: #374151;">
                                                            • {{ $sib->name }} (Roll #{{ $sib->roll_no }}, {{ $sib->class->name ?? 'Class' }})
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span style="color: #9ca3af;">No other enrolled siblings</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Concession Status</span>
                                        <span class="profile-value" style="text-transform: capitalize; font-size: 12px;">
                                            {{ str_replace('_', ' ', $viewingStudent->sibling_concession_status ?? 'none') }}
                                            @if($viewingStudent->sibling_concession_status === 'custom_waiver' && $viewingStudent->custom_concession_amount)
                                                (Rs. {{ number_format($viewingStudent->custom_concession_amount, 2) }})
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- Transport Section --}}
                                <div style="margin-bottom: 25px;">
                                    <div class="profile-section-title">
                                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-6H6V6h12v5z"/></svg>
                                        Transport
                                    </div>
                                    <div class="profile-row">
                                        <span class="profile-label">Mode</span>
                                        <span class="profile-value" style="text-transform: capitalize;">{{ str_replace('_', ' ', $viewingStudent->transport_mode ?? 'None') }}</span>
                                    </div>
                                    @if($viewingStudent->vehicle_number)
                                    <div class="profile-row">
                                        <span class="profile-label">Vehicle No</span>
                                        <span class="profile-value" style="font-family: monospace; font-weight: 600;">{{ $viewingStudent->vehicle_number }}</span>
                                    </div>
                                    @endif
                                </div>

                                {{-- Activities Section --}}
                                <div>
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0zM2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6.5a.5.5 0 0 1-1 0V1H3v14h3.5a.5.5 0 0 1 0 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/><path d="M4.5 3a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/></svg>
                                        Activities
                                    </div>
                                    @php
                                        $activities = array_filter(array_merge(
                                            explode(',', $viewingStudent->sports ?? ''),
                                            explode(',', $viewingStudent->extra_curriculars ?? '')
                                        ));
                                    @endphp
                                    @if(count($activities) > 0)
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            @foreach($activities as $activity)
                                                <span style="background: #fff; border: 1px solid #dee2e6; padding: 3px 10px; border-radius: 15px; font-size: 11px;">{{ trim($activity) }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #999; font-size: 12px;">None specified</span>
                                    @endif
                                </div>
                            </div>

                            {{-- RIGHT MAIN CONTENT --}}
                            <div style="width: 65%; padding: 30px; background: #fff;">
                                
                                {{-- Name Header --}}
                                <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 3px solid #111;">
                                    <h1 style="font-size: 32px; font-weight: 700; color: #111; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 2px;">{{ $viewingStudent->name }}</h1>
                                    <p style="font-size: 14px; color: #666; margin: 0;">Student Profile</p>
                                </div>

                                {{-- Academic Details Section --}}
                                <div style="margin-bottom: 30px;">
                                    <div class="profile-section-title">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917l-7.5-3.5Z"/><path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466 4.176 9.032Z"/></svg>
                                        Academic Details
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                        @php
                                            // Parse class name like "Class 10B" to extract number and section
                                            $className = $viewingStudent->class->name ?? '';
                                            preg_match('/(\d+)([A-Za-z])?/', $className, $matches);
                                            $classNumber = isset($matches[1]) ? $matches[1] : $className;
                                            $sectionLetter = isset($matches[2]) ? strtoupper($matches[2]) : ($viewingStudent->section->name ?? '-');
                                        @endphp
                                        <div class="profile-row">
                                            <span class="profile-label">Class</span>
                                            <span class="profile-value" style="font-weight: 600;">{{ $classNumber ? $classNumber . 'th' : 'N/A' }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Section</span>
                                            <span class="profile-value">{{ $sectionLetter }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Admission No</span>
                                            <span class="profile-value" style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $viewingStudent->admission_no }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Roll No</span>
                                            <span class="profile-value" style="font-family: monospace; font-weight: 700;">{{ $viewingStudent->roll_no }}</span>
                                        </div>
                                        <div class="profile-row">
                                            <span class="profile-label">Admission Date</span>
                                            <span class="profile-value">{{ $viewingStudent->admission_date ? \Carbon\Carbon::parse($viewingStudent->admission_date)->format('d M Y') : '-' }}</span>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>

                {{-- Print Helper --}}
                <script>
                    function printDiv(divId) {
                        var printContents = document.getElementById(divId).innerHTML;
                        var originalContents = document.body.innerHTML;
                        document.body.innerHTML = printContents;
                        window.print();
                        document.body.innerHTML = originalContents;
                        location.reload(); 
                    }
                </script>

            </div>
        </div>
    @endif

    {{-- 3-STEP INSTITUTIONAL DEPARTURE / SLC WIZARD MODAL --}}
    @if($showSlcModal && $slcStudent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-hidden" aria-labelledby="slc-modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="closeSlcModal"></div>

            <div class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border border-slate-200/80 flex flex-col z-10 overflow-hidden transform transition-all">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-amber-600 via-amber-700 to-orange-700 px-6 sm:px-7 py-5 sm:py-6 text-white relative overflow-hidden shrink-0">
                    <div class="absolute -right-8 -top-8 w-36 h-36 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/25 shadow-inner shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold tracking-tight">Institutional Departure & School Leaving Certificate</h3>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-amber-100">
                                    <span class="font-semibold text-white">{{ $slcStudent->name }}</span>
                                    <span>•</span>
                                    <span class="font-mono bg-white/15 px-2 py-0.5 rounded-md text-[11px]">Adm #{{ $slcStudent->admission_no }}</span>
                                </div>
                            </div>
                        </div>
                        <button wire:click="closeSlcModal" class="text-white/70 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    {{-- Modern Interactive Stepper Indicator --}}
                    <div class="flex items-center justify-between mt-6 px-1 relative z-10">
                        {{-- Step 1 --}}
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all {{ $slcStep === 1 ? 'bg-white text-amber-900 shadow-md ring-4 ring-white/20' : ($slcStep > 1 ? 'bg-emerald-500 text-white shadow-sm' : 'bg-black/25 text-white/60') }}">
                                @if($slcStep > 1)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    1
                                @endif
                            </div>
                            <div class="text-left hidden sm:block">
                                <span class="block text-[11px] font-bold leading-tight {{ $slcStep === 1 ? 'text-white' : 'text-amber-100/75' }}">Clearance</span>
                                <span class="block text-[9px] text-amber-200/60 font-medium">Fee Dues</span>
                            </div>
                        </div>

                        {{-- Line 1-2 --}}
                        <div class="flex-1 h-0.5 mx-2.5 {{ $slcStep > 1 ? 'bg-amber-300' : 'bg-white/20' }} rounded-full"></div>

                        {{-- Step 2 --}}
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all {{ $slcStep === 2 ? 'bg-white text-amber-900 shadow-md ring-4 ring-white/20' : ($slcStep > 2 ? 'bg-emerald-500 text-white shadow-sm' : 'bg-black/25 text-white/60') }}">
                                @if($slcStep > 2)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    2
                                @endif
                            </div>
                            <div class="text-left hidden sm:block">
                                <span class="block text-[11px] font-bold leading-tight {{ $slcStep === 2 ? 'text-white' : 'text-amber-100/75' }}">Assessment</span>
                                <span class="block text-[9px] text-amber-200/60 font-medium">Conduct & Reason</span>
                            </div>
                        </div>

                        {{-- Line 2-3 --}}
                        <div class="flex-1 h-0.5 mx-2.5 {{ $slcStep > 2 ? 'bg-amber-300' : 'bg-white/20' }} rounded-full"></div>

                        {{-- Step 3 --}}
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition-all {{ $slcStep === 3 ? 'bg-white text-amber-900 shadow-md ring-4 ring-white/20' : 'bg-black/25 text-white/60' }}">
                                3
                            </div>
                            <div class="text-left hidden sm:block">
                                <span class="block text-[11px] font-bold leading-tight {{ $slcStep === 3 ? 'text-white' : 'text-amber-100/75' }}">Issuance</span>
                                <span class="block text-[9px] text-amber-200/60 font-medium">Preview & Seal</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-7 overflow-y-auto flex-1">
                    @if($issuedSlc)
                        {{-- Success View --}}
                        <div class="text-center py-6">
                            <div class="w-16 h-16 bg-gradient-to-tr from-emerald-500 to-teal-400 text-white rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/20 ring-8 ring-emerald-50">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-1">School Leaving Certificate Issued</h4>
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 border border-amber-200 rounded-xl my-2">
                                <span class="text-xs text-amber-800 font-medium">Official Serial:</span>
                                <span class="font-mono font-bold text-amber-900 text-sm tracking-wider">{{ $issuedSlc->certificate_number }}</span>
                            </div>
                            <p class="text-xs text-gray-500 max-w-md mx-auto mb-6 leading-relaxed">Student profile has officially transitioned to <strong class="text-gray-800">Withdrawn</strong>. All historical academic, attendance, and financial ledgers remain permanently preserved in the institutional 360° audit portfolio.</p>
                            
                            <div class="flex items-center justify-center gap-3 flex-wrap">
                                <a 
                                    href="{{ route('admin.students.slc.print', $issuedSlc->id) }}" 
                                    target="_blank"
                                    class="px-6 py-2.5 bg-gradient-to-r from-amber-600 via-amber-700 to-orange-700 hover:from-amber-700 hover:to-orange-800 text-white font-bold text-sm rounded-xl shadow-md transition-all inline-flex items-center gap-2 transform hover:-translate-y-0.5 active:translate-y-0"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    Print Official SLC (PDF)
                                </a>
                                <button 
                                    type="button" 
                                    wire:click="closeSlcModal"
                                    class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition-all"
                                >
                                    Done
                                </button>
                            </div>
                        </div>
                    @elseif($slcStep === 1)
                        {{-- Step 1: Clearance Check --}}
                        <div class="space-y-5">
                            {{-- Student Summary Pill Grid --}}
                            <div class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Class & Section</span>
                                    <span class="font-bold text-slate-800">{{ $slcEnrollment->classModel->name ?? '-' }} ({{ $slcStudent->section->name ?? ($slcEnrollment->section->name ?? 'A') }})</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Roll Number</span>
                                    <span class="font-bold font-mono text-slate-800">{{ $slcEnrollment->roll_no ?? ($slcStudent->roll_no ?? '-') }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Shift</span>
                                    <span class="font-bold capitalize text-slate-800">{{ $slcEnrollment->shift_type ?? 'Regular' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Session</span>
                                    <span class="font-bold text-slate-800 truncate block">{{ $slcEnrollment->academicSession->name ?? 'Current' }}</span>
                                </div>
                            </div>

                            {{-- Financial Clearance Status --}}
                            @if(\App\Services\LicenseStatus::isModuleEnabled('fees'))
                                @if(!empty($slcClearance['cleared']))
                                    <div class="p-5 bg-gradient-to-br from-emerald-50 via-teal-50/40 to-emerald-50 border border-emerald-200/90 rounded-2xl flex items-start gap-3.5 shadow-sm">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm ring-4 ring-emerald-100">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between flex-wrap gap-2">
                                                <h4 class="text-sm font-bold text-emerald-950">Financial Clearance Certified</h4>
                                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Balance: Rs. 0.00</span>
                                            </div>
                                            <p class="text-xs text-emerald-700/90 mt-1 leading-relaxed">All institutional fee records are fully cleared for the current academic session. Student is fully certified for institutional departure.</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-5 bg-gradient-to-br from-rose-50 via-red-50/40 to-rose-50 border border-rose-300 rounded-2xl shadow-sm">
                                        <div class="flex items-start gap-3.5">
                                            <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-sm ring-4 ring-rose-100">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between flex-wrap gap-2">
                                                    <h4 class="text-sm font-bold text-rose-950">Departure Blocked: Outstanding Fee Arrears</h4>
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-rose-200/80 text-rose-900 border border-rose-300">
                                                        Pending: Rs. {{ number_format($slcClearance['outstanding_balance'] ?? 0, 2) }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-rose-700/90 mt-1 leading-relaxed">
                                                    School policy prohibits issuing School Leaving Certificates with pending dues. Please settle the remaining balance or submit an authorized institutional waiver below.
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Unpaid Records Table --}}
                                        <div class="mt-4 bg-white rounded-xl border border-rose-200/80 overflow-hidden shadow-sm">
                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-rose-100/60 text-rose-900 font-bold border-b border-rose-200/60">
                                                    <tr>
                                                        <th class="p-2.5">Fee Period</th>
                                                        <th class="p-2.5">Status</th>
                                                        <th class="p-2.5 text-right">Balance Due</th>
                                                        <th class="p-2.5 text-right">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-rose-100">
                                                    @foreach($slcClearance['unpaid_records'] ?? [] as $record)
                                                        <tr class="hover:bg-rose-50/50 transition-colors">
                                                            <td class="p-2.5 font-medium text-gray-800">{{ $record['fee_type'] }} ({{ $record['fee_period'] }})</td>
                                                            <td class="p-2.5">
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $record['status'] === 'unpaid' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                                                                    {{ $record['status'] }}
                                                                </span>
                                                            </td>
                                                            <td class="p-2.5 text-right font-mono font-bold text-rose-700">Rs. {{ number_format($record['balance'], 2) }}</td>
                                                            <td class="p-2.5 text-right">
                                                                <button 
                                                                    type="button" 
                                                                    wire:click="openWaiverModal({{ $record['id'] }})"
                                                                    class="px-2.5 py-1 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white rounded-lg text-[11px] font-bold shadow-sm transition-all flex items-center gap-1 ml-auto"
                                                                >
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                                    Apply Waiver
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            @else
                                {{-- Fee Module Disabled by License --}}
                                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-start gap-3">
                                    <div class="p-2 bg-slate-200/70 text-slate-700 rounded-xl shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800">Fee Module Inactive (License Policy)</h4>
                                        <p class="text-xs text-slate-600 mt-0.5">The Fee Management module is disabled under the current software license. Financial clearance is automatically certified for departure.</p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" wire:click="closeSlcModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                                <button 
                                    type="button" 
                                    wire:click="advanceSlcStep"
                                    @if(\App\Services\LicenseStatus::isModuleEnabled('fees') && empty($slcClearance['cleared'])) disabled @endif
                                    class="px-5 py-2.5 text-sm font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5 {{ (\App\Services\LicenseStatus::isModuleEnabled('fees') && empty($slcClearance['cleared'])) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white shadow-md' }}"
                                >
                                    Proceed to Assessment →
                                </button>
                            </div>
                        </div>
                        @elseif($slcStep === 2)
                            {{-- Step 2: Assessment & Reason --}}
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Date of Leaving <span class="text-red-500">*</span></label>
                                        <input wire:model="slcLeavingDate" type="date" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none" />
                                        @error('slcLeavingDate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Reason for Departure <span class="text-red-500">*</span></label>
                                        <select wire:model="slcReason" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none bg-white">
                                            <option value="parent_relocation">Parent Relocation / Residence Change</option>
                                            <option value="school_transfer">Transfer to Another Institution</option>
                                            <option value="course_completion">Completed Course of Studies</option>
                                            <option value="financial_hardship">Financial Constraints</option>
                                            <option value="personal">Personal / Domestic Reasons</option>
                                            <option value="other">Other Institutional Reason</option>
                                        </select>
                                        @error('slcReason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">General Conduct & Character <span class="text-red-500">*</span></label>
                                        <select wire:model="slcConduct" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none bg-white">
                                            <option value="Exemplary">Exemplary</option>
                                            <option value="Very Good">Very Good</option>
                                            <option value="Good">Good</option>
                                            <option value="Satisfactory">Satisfactory</option>
                                            <option value="Needs Improvement">Needs Improvement</option>
                                        </select>
                                        @error('slcConduct') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Institutional Remarks</label>
                                        <input wire:model="slcRemarks" type="text" placeholder="e.g. Eligible for promotion to higher class" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none" />
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                    <button type="button" wire:click="$set('slcStep', 1)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">← Back</button>
                                    <button type="button" wire:click="advanceSlcStep" class="px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white text-sm font-bold rounded-xl shadow-md transition-all">Preview & Confirm →</button>
                                </div>
                            </div>
                        @elseif($slcStep === 3)
                            {{-- Step 3: Certificate Preview & Confirmation --}}
                            <div class="space-y-4">
                                <div class="p-6 border-2 border-amber-300/80 bg-gradient-to-b from-amber-50/60 to-orange-50/40 rounded-2xl relative shadow-inner">
                                    <div class="text-center pb-3 border-b border-amber-200/80">
                                        <span class="text-[10px] uppercase font-bold tracking-widest text-amber-800">Provisional Certificate Specification</span>
                                        <h4 class="text-lg font-bold text-gray-900 mt-0.5">Official School Leaving Certificate</h4>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3.5 mt-4 text-xs">
                                        <div><span class="text-gray-500">Student Name:</span> <strong class="text-gray-900 block text-sm">{{ $slcStudent->name }}</strong></div>
                                        <div><span class="text-gray-500">Father's Name:</span> <strong class="text-gray-900 block text-sm">{{ $slcStudent->father_name ?? '-' }}</strong></div>
                                        <div><span class="text-gray-500">Admission No:</span> <strong class="font-mono text-gray-900 block">{{ $slcStudent->admission_no }}</strong></div>
                                        <div><span class="text-gray-500">B-Form / CNIC:</span> <strong class="font-mono text-gray-900 block">{{ $slcStudent->b_form_number ?? ($slcStudent->father_cnic ?? '-') }}</strong></div>
                                        <div><span class="text-gray-500">Class & Section:</span> <strong class="text-gray-900 block">{{ $slcEnrollment->classModel->name ?? '-' }} ({{ $slcStudent->section->name ?? ($slcEnrollment->section->name ?? '-') }})</strong></div>
                                        <div><span class="text-gray-500">Date of Leaving:</span> <strong class="text-gray-900 block">{{ $slcLeavingDate }}</strong></div>
                                        <div><span class="text-gray-500">Reason:</span> <strong class="capitalize text-gray-900 block">{{ str_replace('_', ' ', $slcReason) }}</strong></div>
                                        <div><span class="text-gray-500">Conduct:</span> <strong class="text-gray-900 block">{{ $slcConduct }}</strong></div>
                                    </div>
                                </div>

                                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <div class="leading-relaxed">
                                        <strong>Important Institutional Audit Notice:</strong> Issuing this certificate will update the student status to <em>Withdrawn</em> and terminate the active session enrollment. All historical academic, attendance, and financial ledgers will be permanently protected and accessible in the 360° Lifetime Portfolio.
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                    <button type="button" wire:click="$set('slcStep', 2)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">← Back</button>
                                    <button 
                                        type="button" 
                                        wire:click="finalizeSlc"
                                        wire:loading.attr="disabled"
                                        class="px-6 py-2.5 bg-gradient-to-r from-amber-600 via-amber-700 to-orange-700 hover:from-amber-700 hover:to-orange-800 text-white text-sm font-bold rounded-xl shadow-md transition-all flex items-center gap-2 transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        <span wire:loading.remove wire:target="finalizeSlc" class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Issue Certificate & Seal Archive
                                        </span>
                                        <span wire:loading wire:target="finalizeSlc" class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Issuing Certificate & Archiving...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- FEE WAIVER / DEBT ADJUSTMENT MODAL --}}
    @if($showWaiverModal && $waiverFeeRecord)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-hidden" aria-labelledby="waiver-modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="closeWaiverModal"></div>

            <div class="relative w-full max-w-lg max-h-[90vh] bg-white rounded-3xl shadow-2xl border border-slate-200/80 flex flex-col z-10 overflow-hidden transform transition-all">
                <div class="bg-gradient-to-r from-purple-700 via-indigo-700 to-purple-800 px-6 py-5 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center border border-white/20 shadow-inner shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold">Apply Institutional Fee Waiver</h3>
                            <p class="text-[11px] text-purple-200">{{ $waiverFeeRecord->student->name ?? 'Student' }} • Record #{{ $waiverFeeRecord->id }}</p>
                        </div>
                    </div>
                    <button wire:click="closeWaiverModal" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <div class="p-3.5 bg-gradient-to-br from-purple-50 to-indigo-50/60 rounded-2xl border border-purple-100 text-xs">
                        <div class="flex justify-between mb-1.5">
                            <span class="text-purple-700 font-medium">Fee Period:</span>
                            <span class="font-bold text-purple-950">{{ $waiverFeeRecord->fee_type }} ({{ $waiverFeeRecord->fee_period }})</span>
                        </div>
                        <div class="flex justify-between mb-1.5">
                            <span class="text-purple-700 font-medium">Original Invoice:</span>
                            <span class="font-mono text-purple-950">Rs. {{ number_format($waiverFeeRecord->amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between pt-1.5 border-t border-purple-200/50">
                            <span class="text-purple-700 font-bold">Outstanding Balance:</span>
                            <span class="font-mono font-bold text-rose-600 text-sm">Rs. {{ number_format($waiverFeeRecord->balance, 2) }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700">Waiver Amount (Rs.) <span class="text-red-500">*</span></label>
                            <div class="flex gap-1.5">
                                <button 
                                    type="button" 
                                    wire:click="$set('waiverAmount', '{{ $waiverFeeRecord->balance }}')"
                                    class="px-2 py-0.5 rounded bg-purple-100 hover:bg-purple-200 text-purple-800 text-[10px] font-bold transition-colors"
                                >
                                    100% (Full)
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="$set('waiverAmount', '{{ round($waiverFeeRecord->balance / 2, 2) }}')"
                                    class="px-2 py-0.5 rounded bg-purple-100 hover:bg-purple-200 text-purple-800 text-[10px] font-bold transition-colors"
                                >
                                    50%
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="$set('waiverAmount', '{{ round($waiverFeeRecord->balance / 4, 2) }}')"
                                    class="px-2 py-0.5 rounded bg-purple-100 hover:bg-purple-200 text-purple-800 text-[10px] font-bold transition-colors"
                                >
                                    25%
                                </button>
                            </div>
                        </div>
                        <input wire:model="waiverAmount" type="number" step="0.01" max="{{ $waiverFeeRecord->balance }}" required class="w-full px-3.5 py-2 text-sm font-mono rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none" />
                        @error('waiverAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Approving Authority <span class="text-red-500">*</span></label>
                            <input wire:model="waiverApprovedBy" type="text" placeholder="e.g. Principal" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none" />
                            @error('waiverApprovedBy') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Order / Diary Ref <span class="text-red-500">*</span></label>
                            <input wire:model="waiverReference" type="text" placeholder="e.g. APP-2026-99" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none" />
                            @error('waiverReference') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Concession Category</label>
                        <select wire:model="waiverConcessionType" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none bg-white">
                            <option value="hardship">Financial Hardship / Need-Based</option>
                            <option value="departure">Institutional Departure Clearance</option>
                            <option value="management_discretion">Management Discretion / Board Order</option>
                            <option value="sibling_departure">Sibling Policy Adjustment</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Official Justification / Reason <span class="text-red-500">*</span></label>
                        <textarea wire:model="waiverReason" rows="2" placeholder="e.g. Humanitarian departure clearance authorized by Principal." required class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none"></textarea>
                        @error('waiverReason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" wire:click="closeWaiverModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                        <button 
                            type="button" 
                            wire:click="submitFeeWaiver"
                            wire:loading.attr="disabled"
                            class="px-5 py-2.5 bg-gradient-to-r from-purple-700 to-indigo-700 hover:from-purple-800 hover:to-indigo-800 text-white text-sm font-bold rounded-xl shadow-md transition-all flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove wire:target="submitFeeWaiver">Apply & Post to Ledger</span>
                            <span wire:loading wire:target="submitFeeWaiver" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Posting Waiver...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 360° LIFETIME PORTFOLIO & AUDIT TIMELINE MODAL --}}
    @if($showHistoryModal && $historyPortfolio)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-hidden" aria-labelledby="history-modal-title" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity no-print" wire:click="closeHistoryModal"></div>

            {{-- Dialog Shell --}}
            <div id="printable-portfolio" class="relative w-full max-w-4xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border border-slate-200 flex flex-col z-10 overflow-hidden transform transition-all">
                {{-- Header (pinned top, shrink-0) --}}
                <div class="bg-gradient-to-r from-slate-50 via-indigo-50/50 to-slate-50 px-6 sm:px-8 py-5 sm:py-6 border-b border-slate-200/90 shrink-0 relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="flex items-center justify-between relative z-10 gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            @if(!empty($historyPortfolio['student']['profile_photo_path']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($historyPortfolio['student']['profile_photo_path']) }}" alt="{{ $historyPortfolio['student']['name'] }}" class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl object-cover ring-2 ring-indigo-200 shadow-sm shrink-0">
                            @else
                                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 flex items-center justify-center text-white font-black text-2xl shadow-sm ring-4 ring-indigo-100 shrink-0">
                                    {{ substr($historyPortfolio['student']['name'] ?? 'S', 0, 1) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 id="history-modal-title" class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 truncate">{{ $historyPortfolio['student']['name'] }}</h3>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ ($historyPortfolio['student']['status'] ?? '') === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ ($historyPortfolio['student']['status'] ?? '') === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                                        {{ ($historyPortfolio['student']['status'] ?? '') === 'withdrawn' ? 'SLC Issued' : ucfirst($historyPortfolio['student']['status'] ?? 'Unknown') }}
                                    </span>
                                    <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-700 border border-indigo-200">
                                        <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                        360° Lifetime Portfolio
                                    </span>
                                </div>
                                <div class="text-xs text-slate-600 mt-1.5 flex items-center gap-2 flex-wrap font-medium">
                                    <span class="font-mono bg-white px-2 py-0.5 rounded text-[11px] font-bold text-indigo-700 border border-indigo-200 shadow-xs">Adm #{{ $historyPortfolio['student']['admission_no'] }}</span>
                                    <span>•</span>
                                    <span>Enrolled: <strong class="text-slate-800 font-bold">{{ $historyPortfolio['student']['original_admission_date'] ?? ($historyPortfolio['student']['admission_date'] ?? '-') }}</strong></span>
                                    @if(!empty($historyPortfolio['student']['father_name']))
                                        <span>•</span>
                                        <span class="truncate">S/D of <strong class="text-slate-800 font-bold">{{ $historyPortfolio['student']['father_name'] }}</strong></span>
                                    @endif
                                    @if(!empty($historyPortfolio['student']['b_form_number']))
                                        <span>•</span>
                                        <span class="font-mono text-[11px] text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200">B-Form: {{ $historyPortfolio['student']['b_form_number'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 no-print">
                            <button type="button" onclick="window.print()" class="text-slate-500 hover:text-indigo-600 p-2 rounded-xl hover:bg-white border border-transparent hover:border-slate-200 transition-colors" title="Print Student 360 Portfolio">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </button>
                            <button type="button" wire:click="closeHistoryModal" class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-slate-200/60 transition-colors" title="Close Portfolio">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Quick Stats KPI Cards --}}
                    <div class="grid grid-cols-2 {{ \App\Services\LicenseStatus::isModuleEnabled('fees') ? 'md:grid-cols-4' : 'md:grid-cols-3' }} gap-3 sm:gap-3.5 mt-5 text-xs relative z-10">
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200 shadow-xs hover:border-indigo-300 transition-all">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="block text-[10px] uppercase font-bold tracking-wider">Sessions Enrolled</span>
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block">{{ count($historyPortfolio['sessions']) }}</span>
                            <span class="text-[11px] text-slate-500 block mt-0.5">Cross-Session History</span>
                        </div>

                        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200 shadow-xs hover:border-indigo-300 transition-all">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="block text-[10px] uppercase font-bold tracking-wider">Lifetime Attendance</span>
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block">{{ $historyPortfolio['attendance_rate'] }}%</span>
                            <div class="w-full bg-slate-100 h-2 rounded-full mt-2 overflow-hidden border border-slate-200/50">
                                <div class="h-full rounded-full transition-all duration-500 {{ $historyPortfolio['attendance_rate'] >= 85 ? 'bg-emerald-500' : ($historyPortfolio['attendance_rate'] >= 75 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $historyPortfolio['attendance_rate'] }}%"></div>
                            </div>
                        </div>

                        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200 shadow-xs hover:border-indigo-300 transition-all">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="block text-[10px] uppercase font-bold tracking-wider">Academic Exams</span>
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                            </div>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block">{{ $historyPortfolio['exams_summary']['total_exams'] }} Exams</span>
                            <span class="text-[11px] text-emerald-600 font-bold block mt-0.5">{{ $historyPortfolio['exams_summary']['passed_exams'] ?? 0 }} Passed Overall</span>
                        </div>

                        @if(\App\Services\LicenseStatus::isModuleEnabled('fees'))
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200 shadow-xs hover:border-indigo-300 transition-all">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="block text-[10px] uppercase font-bold tracking-wider">Financial Ledger</span>
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <span class="text-xl sm:text-2xl font-black font-mono mt-1 block {{ ($historyPortfolio['financial_summary']['outstanding_balance'] ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                @if(($historyPortfolio['financial_summary']['outstanding_balance'] ?? 0) > 0)
                                    Rs. {{ number_format($historyPortfolio['financial_summary']['outstanding_balance'] ?? 0, 0) }}
                                @else
                                    Cleared
                                @endif
                            </span>
                            <span class="text-[11px] text-slate-500 block mt-0.5">Total Invoiced: Rs. {{ number_format($historyPortfolio['financial_summary']['total_billed'] ?? 0, 0) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Scrollable Timeline Body (flex-1 overflow-y-auto) --}}
                <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6 bg-slate-50/70">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Institutional Session Audit Trail</h4>
                        </div>
                        <span class="text-xs font-medium text-slate-500">Total Recorded Sessions: <strong class="text-slate-800">{{ count($historyPortfolio['sessions']) }}</strong></span>
                    </div>

                    <div class="relative pl-6 sm:pl-8 space-y-6 sm:space-y-8">
                        {{-- Timeline Continuous Line --}}
                        <div class="absolute left-2.5 sm:left-3.5 top-3 bottom-3 w-0.5 bg-indigo-200 pointer-events-none"></div>

                        @forelse($historyPortfolio['sessions'] as $session)
                            <div class="relative group">
                                {{-- Centered Timeline Node Bullet --}}
                                @if($session['is_active_session'])
                                    <div class="absolute -left-6 sm:-left-8 top-5 w-5 h-5 rounded-full bg-indigo-600 border-2 border-white ring-4 ring-indigo-100 shadow-sm flex items-center justify-center z-10 -translate-x-[2px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                    </div>
                                @else
                                    <div class="absolute -left-6 sm:-left-8 top-5.5 w-4 h-4 rounded-full bg-slate-400 border-2 border-white ring-2 ring-slate-200 shadow-xs z-10"></div>
                                @endif

                                <div class="bg-white rounded-2xl p-5 sm:p-6 border {{ $session['is_active_session'] ? 'border-indigo-300 shadow-sm ring-1 ring-indigo-50' : 'border-slate-200' }} hover:border-indigo-300 hover:shadow-md transition-all">
                                    {{-- Session Header --}}
                                    <div class="flex items-center justify-between flex-wrap gap-2 mb-4 pb-3 border-b border-slate-100">
                                        <div>
                                            <div class="flex items-center gap-2.5 flex-wrap">
                                                <h5 class="font-black text-slate-900 text-base sm:text-lg">{{ $session['session_name'] }}</h5>
                                                @if($session['is_active_session'])
                                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 flex items-center gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                                                        Active Session
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2 mt-1.5 text-xs text-slate-500 flex-wrap">
                                                <span>Class: <strong class="text-slate-800 font-semibold">{{ $session['class_name'] }} ({{ $session['section_name'] }})</strong></span>
                                                <span>•</span>
                                                <span>Roll #<strong class="font-mono text-slate-800 font-bold">{{ $session['roll_no'] }}</strong></span>
                                                <span>•</span>
                                                <span>Shift: <strong class="capitalize text-slate-800 font-semibold">{{ $session['shift_type'] }}</strong></span>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $session['enrollment_status'] === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($session['enrollment_status'] === 'slc_issued' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                            {{ $session['enrollment_status'] === 'slc_issued' ? 'SLC Issued' : ucfirst($session['enrollment_status']) }}
                                        </span>
                                    </div>

                                    {{-- Stats in this Session --}}
                                    <div class="grid grid-cols-1 {{ \App\Services\LicenseStatus::isModuleEnabled('fees') ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }} gap-3 text-xs mb-3">
                                        <div class="p-3.5 bg-slate-50/90 rounded-xl border border-slate-200/70">
                                            <div class="flex items-center justify-between text-slate-500">
                                                <span class="block text-[10px] uppercase font-bold tracking-wider">Attendance</span>
                                                <span class="text-[10px] font-semibold text-slate-400">({{ $session['attendance']['present'] }}/{{ $session['attendance']['total'] }} days)</span>
                                            </div>
                                            <span class="font-black text-slate-900 text-lg mt-0.5 block">{{ $session['attendance']['percentage'] }}%</span>
                                            <div class="w-full bg-slate-200 h-1.5 rounded-full mt-2 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $session['attendance']['percentage'] >= 85 ? 'bg-emerald-500' : ($session['attendance']['percentage'] >= 75 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $session['attendance']['percentage'] }}%"></div>
                                            </div>
                                        </div>

                                        <div class="p-3.5 bg-slate-50/90 rounded-xl border border-slate-200/70">
                                            <div class="flex items-center justify-between text-slate-500">
                                                <span class="block text-[10px] uppercase font-bold tracking-wider">Exams Performance</span>
                                                <span class="text-[10px] font-semibold text-slate-400">{{ $session['exams']['total'] }} Total</span>
                                            </div>
                                            <span class="font-black text-slate-900 text-lg mt-0.5 block">{{ $session['exams']['passed'] }} / {{ $session['exams']['total'] }} Passed</span>
                                            <span class="text-[11px] text-slate-600 block mt-1 font-medium">Average Marks: <strong class="text-slate-900 font-bold">{{ $session['exams']['avg_marks'] }}%</strong></span>
                                        </div>

                                        @if(\App\Services\LicenseStatus::isModuleEnabled('fees'))
                                        <div class="p-3.5 bg-slate-50/90 rounded-xl border border-slate-200/70">
                                            <div class="flex items-center justify-between text-slate-500">
                                                <span class="block text-[10px] uppercase font-bold tracking-wider">Financial Status</span>
                                                <span class="text-[10px] font-semibold text-slate-400">Ledger</span>
                                            </div>
                                            <span class="font-black font-mono text-lg mt-0.5 block {{ ($session['fees']['balance'] ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                {{ ($session['fees']['balance'] ?? 0) > 0 ? 'Dues: Rs. ' . number_format($session['fees']['balance'], 0) : 'Fully Cleared' }}
                                            </span>
                                            <span class="text-[11px] text-slate-600 block mt-1 font-medium">Billed: Rs. {{ number_format($session['fees']['invoiced'] ?? 0, 0) }}</span>
                                        </div>
                                        @endif
                                    </div>

                                    {{-- School Leaving Certificate in this session if any --}}
                                    @if(!empty($session['slc']))
                                        <div class="p-4 bg-gradient-to-r from-amber-50 to-orange-50/80 rounded-xl border border-amber-200 text-xs shadow-xs mt-3.5">
                                            <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                                                <span class="font-bold text-amber-950 flex items-center gap-2 text-sm">
                                                    <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    Official Certificate: <span class="font-mono text-amber-900 font-black">{{ $session['slc']['certificate_number'] }}</span>
                                                </span>
                                                <a 
                                                    href="{{ route('admin.students.slc.print', $session['slc']['id']) }}" 
                                                    target="_blank"
                                                    class="px-3.5 py-1.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white rounded-lg text-xs font-bold shadow-sm inline-flex items-center gap-1.5 transition-all transform hover:-translate-y-0.5 no-print"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                    Print Official SLC
                                                </a>
                                            </div>
                                            <p class="text-xs text-amber-900/90 leading-relaxed font-medium">
                                                Leaving Date: <strong class="text-amber-950 font-bold">{{ $session['slc']['leaving_date'] }}</strong> • Reason: <strong class="text-amber-950 font-bold">{{ ucfirst(str_replace('_', ' ', $session['slc']['reason_for_leaving'])) }}</strong> • Conduct: <strong class="text-amber-950 font-bold">{{ $session['slc']['conduct'] }}</strong>
                                                @if(!empty($session['slc']['remarks']))
                                                    • Remarks: <em class="text-amber-800">"{{ $session['slc']['remarks'] }}"</em>
                                                @endif
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10 bg-white rounded-2xl border border-dashed border-slate-300">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                <p class="text-sm font-semibold text-slate-600">No historical enrollment records found.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Historical records will populate as sessions advance.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Footer (fixed bottom, shrink-0) --}}
                <div class="p-4 sm:p-5 bg-white border-t border-slate-200 flex items-center justify-between shrink-0 no-print">
                    <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Institutional Audit Trail & Historical Protection Active</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="window.print()" class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm rounded-xl transition-all shadow-xs flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print Portfolio
                        </button>
                        <button type="button" wire:click="closeHistoryModal" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm rounded-xl transition-all shadow-sm hover:shadow active:scale-95">
                            Close Portfolio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
