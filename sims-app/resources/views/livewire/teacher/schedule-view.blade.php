<div class="space-y-4 sm:space-y-6">
    {{-- Top Header & Action Controls --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">My Schedule</h1>
                @if(!$isCurrentDateToday)
                    <button 
                        type="button" 
                        wire:click="jumpToToday"
                        class="md:hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 hover:bg-blue-200 border border-blue-200 transition-all"
                        title="Jump to Today's schedule"
                    >
                        <span>Today</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-1">
                <p class="text-[11px] sm:text-xs font-semibold text-blue-600 uppercase tracking-wider">
                    {{ \App\Models\Setting::getGlobal('institute_formal_name', \App\Models\Setting::getGlobal('institute_name', 'SIMS')) }}
                </p>
                <span class="text-gray-300">•</span>
                @if($scheduleType === 'single_schedule')
                    <span class="inline-flex items-center gap-1 px-2 sm:px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Same timetable template applies for all weekdays">
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Universal Schedule</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 sm:px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Day-Wise Timetable</span>
                    </span>
                @endif
                <span class="inline-flex items-center gap-1 px-2 sm:px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ \Carbon\Carbon::parse($targetDate)->format('D, M d, Y') }}</span>
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            @if(!$isCurrentDateToday)
                <button 
                    type="button" 
                    wire:click="jumpToToday" 
                    class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 transition-all shadow-xs"
                >
                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Jump to Today</span>
                </button>
            @endif

            <button 
                onclick="window.print()" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all shadow-xs"
            >
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Print</span>
            </button>
        </div>
    </div>

    {{-- Quick Glance Workload Metrics (Mobile-first responsive pills) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 print:hidden">
        {{-- Total Teaching Classes Today --}}
        <div class="p-2.5 sm:p-3 bg-white border border-gray-150 rounded-xl sm:rounded-2xl shadow-xs flex items-center gap-3">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">Classes Today</div>
                <div class="text-base sm:text-xl font-extrabold text-gray-900 mt-0.5">
                    {{ $summaryStats['teaching_classes'] }}
                    <span class="text-[10px] sm:text-xs font-normal text-gray-400">({{ $summaryStats['regular_classes'] }} reg)</span>
                </div>
            </div>
        </div>

        {{-- Arrangement Duties --}}
        <div class="p-2.5 sm:p-3 bg-white border border-gray-150 rounded-xl sm:rounded-2xl shadow-xs flex items-center gap-3 {{ $summaryStats['arrangement_duties'] > 0 ? 'border-amber-200 bg-amber-50/30' : '' }}">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl {{ $summaryStats['arrangement_duties'] > 0 ? 'bg-amber-100 text-amber-700 border border-amber-200' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">Arrangements</div>
                <div class="text-base sm:text-xl font-extrabold {{ $summaryStats['arrangement_duties'] > 0 ? 'text-amber-800' : 'text-gray-900' }} mt-0.5">
                    {{ $summaryStats['arrangement_duties'] }}
                    @if($summaryStats['override_count'] > 0)
                        <span class="text-[10px] font-bold text-red-600">({{ $summaryStats['override_count'] }} ovr)</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Relieved Periods --}}
        <div class="p-2.5 sm:p-3 bg-white border border-gray-150 rounded-xl sm:rounded-2xl shadow-xs flex items-center gap-3 {{ $summaryStats['relieved_periods'] > 0 ? 'border-purple-200 bg-purple-50/30' : '' }}">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl {{ $summaryStats['relieved_periods'] > 0 ? 'bg-purple-100 text-purple-700 border border-purple-200' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">Relieved</div>
                <div class="text-base sm:text-xl font-extrabold {{ $summaryStats['relieved_periods'] > 0 ? 'text-purple-800' : 'text-gray-900' }} mt-0.5">
                    {{ $summaryStats['relieved_periods'] }}
                </div>
            </div>
        </div>

        {{-- Free Periods --}}
        <div class="p-2.5 sm:p-3 bg-white border border-gray-150 rounded-xl sm:rounded-2xl shadow-xs flex items-center gap-3">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider truncate">Free Periods</div>
                <div class="text-base sm:text-xl font-extrabold text-blue-900 mt-0.5">
                    {{ $summaryStats['free_periods'] }}
                </div>
            </div>
        </div>
    </div>

    {{-- Arrangement Updates Notice Banner --}}
    @if(($arrangementCount ?? 0) > 0 || ($relievedCount ?? 0) > 0)
        <div class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl border border-amber-200 bg-amber-50/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:border-gray-300">
            <div class="flex items-start gap-2.5 sm:gap-3">
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-500/20 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-amber-900 flex items-center gap-1.5">
                        <span>Arrangement Notice for {{ $selectedDay }}</span>
                        <span class="text-amber-700 font-normal">({{ \Carbon\Carbon::parse($targetDate)->format('M d') }})</span>
                    </h4>
                    <div class="text-[11px] sm:text-xs text-amber-800 mt-1 space-y-0.5">
                        @if(($arrangementCount ?? 0) > 0)
                            <p>
                                • <span class="font-bold">{{ $arrangementCount }} arrangement duty(ies)</span> assigned
                                @if(($overrideCount ?? 0) > 0)
                                    <span class="font-bold text-red-700">(including {{ $overrideCount }} admin override replacing your regular period)</span>
                                @endif.
                            </p>
                        @endif
                        @if(($relievedCount ?? 0) > 0)
                            <p>
                                • Relieved from teaching in <span class="font-bold">{{ $relievedCount }} period(s)</span> (covered by substitute teachers).
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <span class="px-2.5 py-1 rounded-lg sm:rounded-xl bg-amber-200/80 text-amber-900 font-bold text-[10px] sm:text-xs border border-amber-300">
                    {{ $arrangementCount ?? 0 }} Duty(ies) Assigned
                </span>
            </div>
        </div>
    @endif

    {{-- Day Switcher: Horizontal Touch Scroll on Mobile, Wrapped Buttons on Desktop --}}
    <div class="print:hidden">
        {{-- Mobile Scrollable Strip with Snap Scrolling --}}
        <div class="flex md:hidden overflow-x-auto no-scrollbar scroll-smooth snap-x gap-2 pb-1 -mx-3 px-3">
            @foreach($days as $day)
                @php
                    $tabDate = $dayDates[$day] ?? $targetDate;
                    $isTodayTab = ($tabDate === now()->format('Y-m-d'));
                    $carbonDate = \Carbon\Carbon::parse($tabDate);
                    $isSelected = ($selectedDay === $day);
                    $shortDay = substr($day, 0, 3);
                @endphp
                <button
                    type="button"
                    wire:click="$set('selectedDay', '{{ $day }}')"
                    class="flex flex-col items-center justify-center snap-start min-w-[70px] flex-1 py-2 px-1.5 rounded-xl font-bold text-xs transition-all border shrink-0 select-none {{ $isSelected ? 'bg-blue-600 text-white border-blue-600 shadow-md ring-2 ring-blue-500/20' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200/90' }}"
                >
                    <div class="flex items-center gap-1">
                        <span class="text-xs uppercase tracking-wider">{{ $shortDay }}</span>
                        @if($isTodayTab)
                            <span class="w-1.5 h-1.5 rounded-full {{ $isSelected ? 'bg-amber-300' : 'bg-blue-600' }}"></span>
                        @endif
                    </div>
                    <span class="text-[10px] font-normal mt-0.5 {{ $isSelected ? 'text-blue-100' : 'text-gray-400' }}">
                        {{ $carbonDate->format('d M') }}
                    </span>
                    @if($isTodayTab)
                        <span class="mt-1 px-1 py-0.2 text-[8px] font-extrabold uppercase rounded {{ $isSelected ? 'bg-blue-800 text-white' : 'bg-blue-100 text-blue-700' }}">
                            Today
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Desktop Wrapped Day Tabs --}}
        <div class="hidden md:flex flex-wrap gap-2">
            @foreach($days as $day)
                @php
                    $tabDate = $dayDates[$day] ?? $targetDate;
                    $isTodayTab = ($tabDate === now()->format('Y-m-d'));
                    $carbonDate = \Carbon\Carbon::parse($tabDate);
                    $isSelected = ($selectedDay === $day);
                @endphp
                <button
                    type="button"
                    wire:click="$set('selectedDay', '{{ $day }}')"
                    class="flex flex-col items-start px-4 py-2.5 rounded-xl font-semibold text-sm transition-all border {{ $isSelected ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200' }}"
                >
                    <div class="flex items-center gap-1.5">
                        <span>{{ $day }}</span>
                        @if($isTodayTab)
                            <span class="px-1.5 py-0.2 text-[10px] font-bold uppercase tracking-wider rounded {{ $isSelected ? 'bg-blue-800 text-white' : 'bg-blue-100 text-blue-700' }}">
                                Today
                            </span>
                        @endif
                    </div>
                    <span class="text-[11px] font-normal {{ $isSelected ? 'text-blue-100' : 'text-gray-400' }}">
                        {{ $carbonDate->format('M d') }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Print Header (Strictly for Printing) --}}
    <div class="hidden print:block text-center mb-6">
        @php
            $logoPath = \App\Models\Setting::getGlobal('institute_logo');
            $formalName = \App\Models\Setting::getGlobal('institute_formal_name', \App\Models\Setting::getGlobal('institute_name', 'SIMS'));
            $address = \App\Models\Setting::getGlobal('institute_address');
        @endphp
        @if($logoPath)
            <div style="margin-bottom: 10px;">
                <img src="{{ '/' . $logoPath }}" style="height: 50px; max-width: 150px; object-fit: contain; margin: 0 auto;">
            </div>
        @endif
        <h1 class="text-xl font-bold uppercase">{{ $formalName }}</h1>
        @if($address)
            <p class="text-xs text-gray-500 font-medium">{{ $address }}</p>
        @endif
        <h2 class="text-sm font-bold text-blue-800 mt-2 uppercase tracking-wide">
            {{ Auth::user()->name }} - {{ $selectedDay }} Schedule ({{ \Carbon\Carbon::parse($targetDate)->format('d M Y') }})
        </h2>
        @if($scheduleType === 'single_schedule')
            <p class="text-[11px] text-gray-500 mt-0.5">Universal Single Schedule Mode</p>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- A. MOBILE TIMELINE CARDS VIEW (Clean, touch-friendly, no horizontal-scroll) --}}
    {{-- ========================================================================= --}}
    <div class="block md:hidden print:hidden space-y-3">
        @if($periods->count() > 0)
            @foreach($periods as $period)
                @php
                    $isBreakOrAssembly = $period->is_break || $period->is_assembly;
                    $data = $scheduleDataByPeriod[$period->period_no] ?? [
                        'regular' => null,
                        'assigned_arrangement' => null,
                        'relieved_arrangement' => null,
                        'is_override' => false,
                        'is_relieved' => false,
                    ];
                    $regular = $data['regular'];
                    $arr = $data['assigned_arrangement'];
                    $rel = $data['relieved_arrangement'];
                    $isOverride = $data['is_override'];
                    $isRelieved = $data['is_relieved'];

                    // Live clock calculation for Today
                    $now = now();
                    $startTimeToday = now()->copy()->setTimeFromTimeString($period->start_time);
                    $endTimeToday = now()->copy()->setTimeFromTimeString($period->end_time);
                    $isLiveNow = $isCurrentDateToday && $now->between($startTimeToday, $endTimeToday);
                    $isPast = $isCurrentDateToday && $now->gt($endTimeToday);
                @endphp

                @if($isBreakOrAssembly)
                    {{-- Slim Break or Assembly Card --}}
                    <div class="py-2.5 px-3.5 rounded-xl border border-dashed {{ $period->is_break ? 'bg-amber-50/70 border-amber-300 text-amber-900' : 'bg-purple-50/70 border-purple-300 text-purple-900' }} flex items-center justify-between shadow-2xs">
                        <div class="flex items-center gap-2">
                            @if($period->is_break)
                                <span class="w-6 h-6 rounded-lg bg-amber-200/70 text-amber-800 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                            @else
                                <span class="w-6 h-6 rounded-lg bg-purple-200/70 text-purple-800 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                </span>
                            @endif
                            <span class="text-xs font-bold">{{ $period->label }}</span>
                        </div>
                        <span class="text-[11px] font-semibold opacity-80 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}
                        </span>
                    </div>
                @else
                    {{-- Teaching Period Card --}}
                    @php
                        // Card visual styling based on duty status & active state
                        if ($isLiveNow) {
                            $mobileCardBorder = 'border-2 border-emerald-500 bg-white ring-2 ring-emerald-500/20 shadow-md';
                            $periodBadgeBg = 'bg-emerald-600 text-white';
                        } elseif ($isOverride) {
                            $mobileCardBorder = 'border-l-4 border-l-amber-500 border-gray-200 bg-white shadow-xs';
                            $periodBadgeBg = 'bg-amber-100 text-amber-900 border border-amber-300';
                        } elseif ($arr) {
                            $mobileCardBorder = 'border-l-4 border-l-blue-500 border-gray-200 bg-white shadow-xs';
                            $periodBadgeBg = 'bg-blue-100 text-blue-900 border border-blue-200';
                        } elseif ($isRelieved) {
                            $mobileCardBorder = 'border-l-4 border-l-purple-400 border-gray-200 bg-white/90 shadow-xs';
                            $periodBadgeBg = 'bg-purple-100 text-purple-900 border border-purple-200';
                        } elseif ($regular) {
                            $mobileCardBorder = 'border-l-4 border-l-emerald-500 border-gray-200 bg-white shadow-xs';
                            $periodBadgeBg = 'bg-emerald-50 text-emerald-800 border border-emerald-200';
                        } else {
                            $mobileCardBorder = 'border border-dashed border-gray-200 bg-gray-50/60';
                            $periodBadgeBg = 'bg-gray-100 text-gray-500';
                        }
                    @endphp

                    <div class="rounded-2xl p-3.5 transition-all {{ $mobileCardBorder }}">
                        {{-- Card Header: Period & Time Range + Live/Status tag --}}
                        <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-2.5 mb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold {{ $periodBadgeBg }}">
                                    {{ $period->label }}
                                </span>
                                <span class="text-xs font-medium text-gray-500">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}
                                </span>
                            </div>

                            <div>
                                @if($isLiveNow)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-600 text-white shadow-xs animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                        LIVE NOW
                                    </span>
                                @elseif($isOverride)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        Arrangement (Override)
                                    </span>
                                @elseif($isRelieved)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        Relieved
                                    </span>
                                @elseif($arr)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Arrangement Duty
                                    </span>
                                @elseif($regular)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Regular Class
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-400">
                                        Free Period
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Card Body: Subject, Class, Room & Arrangements --}}
                        <div>
                            @if($isOverride)
                                {{-- Override State --}}
                                @php
                                    $regClass = collect($classes)->firstWhere('id', $regular->class_id);
                                    $regSub = $subjects[$regular->subject_id] ?? null;
                                @endphp
                                <div class="space-y-2">
                                    {{-- Regular Class Struck Through --}}
                                    <div class="line-through text-xs text-gray-400 font-medium flex items-center gap-1.5">
                                        <span>Regular: {{ $regClass->name ?? 'Class' }} • {{ $regSub->name ?? 'Subject' }}</span>
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-red-500 bg-red-50 border border-red-200 px-1 py-0.2 rounded">
                                            Overridden
                                        </span>
                                    </div>

                                    {{-- New Assigned Class & Subject --}}
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 class="text-base font-extrabold text-amber-900">
                                                {{ $arr->subject?->name ?? 'Arrangement Subject' }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                                <span class="px-2.5 py-0.5 text-xs font-bold text-amber-900 bg-amber-100 border border-amber-300 rounded-lg">
                                                    {{ $arr->classRoom?->name ?? 'Arrangement Class' }}
                                                </span>
                                                <span class="px-2 py-0.5 text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200 rounded-lg flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    {{ $arr->classRoom?->room ?? ($arr->timetable?->room ?? 'Room -') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Covering for box --}}
                                    <div class="p-2 rounded-xl bg-amber-50 border border-amber-200/80 text-xs text-amber-900 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <div>
                                            <span class="text-amber-700">Covering for:</span>
                                            <span class="font-bold underline decoration-amber-400">{{ $arr->absentTeacher?->name ?? 'Absent Staff' }}</span>
                                        </div>
                                    </div>
                                </div>

                            @elseif($isRelieved)
                                {{-- Relieved State --}}
                                @php
                                    $regClass = collect($classes)->firstWhere('id', $regular->class_id);
                                    $regSub = $subjects[$regular->subject_id] ?? null;
                                @endphp
                                <div class="space-y-2">
                                    <div class="line-through text-xs text-gray-400 font-medium">
                                        Regular Class: {{ $regClass->name ?? 'Class' }} • {{ $regSub->name ?? 'Subject' }} ({{ $regular->room ?: 'Room -' }})
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-purple-50 border border-purple-200 text-xs text-purple-900 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span>
                                        <div>
                                            <span class="font-bold">You are relieved from this period.</span>
                                            <div class="text-[11px] text-purple-700 mt-0.5">
                                                Covered by: <span class="font-semibold">{{ $rel->substituteTeacher?->name ?? 'Substitute Teacher' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($arr)
                                {{-- Free Period Arrangement --}}
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 class="text-base font-extrabold text-blue-900">
                                                {{ $arr->subject?->name ?? 'Arrangement Subject' }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                                <span class="px-2.5 py-0.5 text-xs font-bold text-blue-900 bg-blue-100 border border-blue-300 rounded-lg">
                                                    {{ $arr->classRoom?->name ?? 'Arrangement Class' }}
                                                </span>
                                                <span class="px-2 py-0.5 text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200 rounded-lg flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    {{ $arr->classRoom?->room ?? ($arr->timetable?->room ?? 'Room -') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-2 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <div>
                                            <span class="text-blue-700">Covering for:</span>
                                            <span class="font-bold underline decoration-blue-400">{{ $arr->absentTeacher?->name ?? 'Absent Staff' }}</span>
                                        </div>
                                    </div>
                                </div>

                            @elseif($regular)
                                {{-- Regular Class --}}
                                @php
                                    $classObj = collect($classes)->firstWhere('id', $regular->class_id);
                                    $subObj = $subjects[$regular->subject_id] ?? null;
                                @endphp
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-extrabold text-gray-900">
                                            {{ $subObj->name ?? 'Subject' }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                            <span class="px-2.5 py-0.5 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg">
                                                {{ $classObj->name ?? '-' }}
                                            </span>
                                            @if($regular->room)
                                                <span class="px-2 py-0.5 text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200 rounded-lg flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    {{ $regular->room }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($isPast)
                                        <span class="text-[11px] font-semibold text-gray-400 bg-gray-50 px-2 py-1 rounded-md">
                                            Finished
                                        </span>
                                    @endif
                                </div>

                            @else
                                {{-- Free Period --}}
                                <div class="py-1 text-xs text-gray-400 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                        <span>No class scheduled &bull; Free time / Lesson preparation</span>
                                    </div>
                                    <span class="text-[11px] text-gray-400 font-medium">Free</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        @else
            <div class="p-8 text-center bg-white rounded-2xl border border-gray-200">
                <h3 class="text-sm font-bold text-gray-800">No Periods Configured</h3>
                <p class="text-xs text-gray-500 mt-1">Please contact school administration to configure schedule periods.</p>
            </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- B. DESKTOP TABLE VIEW (Spacious table layout for tablets, desktop & print) --}}
    {{-- ========================================================================= --}}
    <div class="hidden md:block print:block glass-card rounded-2xl overflow-hidden border border-gray-200 shadow-xs">
        @if($periods->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/75">
                        <tr>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Period</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Time</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Class</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Subject</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Room</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Duty Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($periods as $period)
                            @if($period->is_break || $period->is_assembly)
                                <tr class="{{ $period->is_break ? 'bg-yellow-50/70' : 'bg-purple-50/70' }}">
                                    <td class="px-4 py-3 text-sm font-bold {{ $period->is_break ? 'text-yellow-800' : 'text-purple-800' }}">
                                        {{ $period->label }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 font-medium whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}
                                    </td>
                                    <td colspan="4" class="px-4 py-3 text-center text-sm font-semibold {{ $period->is_break ? 'text-yellow-700' : 'text-purple-700' }}">
                                        {{ $period->label }}
                                    </td>
                                </tr>
                            @else
                                @php
                                    $data = $scheduleDataByPeriod[$period->period_no] ?? [
                                        'regular' => null,
                                        'assigned_arrangement' => null,
                                        'relieved_arrangement' => null,
                                        'is_override' => false,
                                        'is_relieved' => false,
                                    ];
                                    $regular = $data['regular'];
                                    $arr = $data['assigned_arrangement'];
                                    $rel = $data['relieved_arrangement'];
                                    $isOverride = $data['is_override'];
                                    $isRelieved = $data['is_relieved'];

                                    $rowBgClass = 'hover:bg-gray-50/75';
                                    if ($isOverride) {
                                        $rowBgClass = 'bg-amber-50/40 hover:bg-amber-50/70 border-l-4 border-l-amber-500';
                                    } elseif ($arr) {
                                        $rowBgClass = 'bg-blue-50/30 hover:bg-blue-50/60 border-l-4 border-l-blue-500';
                                    } elseif ($isRelieved) {
                                        $rowBgClass = 'bg-purple-50/20 hover:bg-purple-50/50 border-l-4 border-l-purple-400';
                                    }
                                @endphp
                                <tr class="{{ $rowBgClass }} transition-colors">
                                    {{-- Period --}}
                                    <td class="px-4 py-3 text-sm font-bold text-gray-800">
                                        {{ $period->label }}
                                    </td>

                                    {{-- Time --}}
                                    <td class="px-4 py-3 text-sm text-gray-600 font-medium whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}
                                    </td>

                                    {{-- Class Column --}}
                                    <td class="px-4 py-3">
                                        @if($isOverride)
                                            {{-- Admin Override: Regular period is crossed out and shows arrangement --}}
                                            @php
                                                $regClass = collect($classes)->firstWhere('id', $regular->class_id);
                                            @endphp
                                            <div class="space-y-1.5">
                                                <div class="flex items-center gap-1.5 text-xs">
                                                    <span class="line-through text-gray-400 font-medium px-2 py-0.5 rounded bg-gray-100 border border-gray-200">
                                                        {{ $regClass->name ?? 'Regular Class' }}
                                                    </span>
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded">
                                                        Overridden
                                                    </span>
                                                </div>
                                                <div class="flex flex-col">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="px-2.5 py-1 text-sm font-bold text-amber-900 bg-amber-100 border border-amber-300 rounded-lg shadow-xs">
                                                            {{ $arr->classRoom?->name ?? 'Arrangement Class' }}
                                                        </span>
                                                        <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-amber-800 bg-amber-200/70 border border-amber-300 rounded-full">
                                                            Arrangement
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-amber-800 font-medium mt-1 flex items-center gap-1">
                                                        <span>Covering for:</span>
                                                        <span class="font-bold underline decoration-amber-400">{{ $arr->absentTeacher?->name ?? 'Absent Staff' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($isRelieved)
                                            {{-- Relieved: Regular period crossed out, covered by substitute --}}
                                            @php
                                                $regClass = collect($classes)->firstWhere('id', $regular->class_id);
                                            @endphp
                                            <div>
                                                <div class="flex items-center gap-1.5 text-xs">
                                                    <span class="line-through text-gray-400 font-medium px-2 py-0.5 rounded bg-gray-100 border border-gray-200">
                                                        {{ $regClass->name ?? 'Regular Class' }}
                                                    </span>
                                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 bg-purple-50 border border-purple-200 px-1.5 py-0.5 rounded">
                                                        Relieved
                                                    </span>
                                                </div>
                                                <div class="text-[11px] text-purple-700 font-semibold mt-1">
                                                    Covered by: {{ $rel->substituteTeacher?->name ?? 'Substitute Teacher' }}
                                                </div>
                                            </div>
                                        @elseif($arr)
                                            {{-- Arrangement on Free Period --}}
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="px-2.5 py-1 text-sm font-bold text-blue-900 bg-blue-100 border border-blue-300 rounded-lg shadow-xs">
                                                        {{ $arr->classRoom?->name ?? 'Arrangement Class' }}
                                                    </span>
                                                    <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-blue-800 bg-blue-200/70 border border-blue-300 rounded-full">
                                                        Arrangement Duty
                                                    </span>
                                                </div>
                                                <div class="text-[11px] text-blue-800 font-medium mt-1 flex items-center gap-1">
                                                    <span>Covering for:</span>
                                                    <span class="font-bold underline decoration-blue-400">{{ $arr->absentTeacher?->name ?? 'Absent Staff' }}</span>
                                                </div>
                                            </div>
                                        @elseif($regular)
                                            {{-- Normal Regular Class --}}
                                            @php
                                                $classObj = collect($classes)->firstWhere('id', $regular->class_id);
                                            @endphp
                                            <span class="px-2.5 py-1 text-sm font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg">
                                                {{ $classObj->name ?? '-' }}
                                            </span>
                                        @else
                                            {{-- Free Period --}}
                                            <span class="px-2.5 py-1 text-xs font-medium text-gray-400 bg-gray-50 border border-gray-200/60 rounded-md">
                                                Free
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Subject Column --}}
                                    <td class="px-4 py-3">
                                        @if($isOverride)
                                            @php
                                                $regSub = $subjects[$regular->subject_id] ?? null;
                                            @endphp
                                            <div class="space-y-1">
                                                <div class="line-through text-gray-400 text-xs font-normal">
                                                    {{ $regSub->name ?? '-' }}
                                                </div>
                                                <div class="text-sm font-bold text-amber-900">
                                                    {{ $arr->subject?->name ?? 'Arrangement' }}
                                                </div>
                                            </div>
                                        @elseif($isRelieved)
                                            @php
                                                $regSub = $subjects[$regular->subject_id] ?? null;
                                            @endphp
                                            <div class="line-through text-gray-400 text-sm">
                                                {{ $regSub->name ?? '-' }}
                                            </div>
                                        @elseif($arr)
                                            <div class="text-sm font-bold text-blue-900">
                                                {{ $arr->subject?->name ?? 'Arrangement' }}
                                            </div>
                                        @elseif($regular)
                                            @php
                                                $regSub = $subjects[$regular->subject_id] ?? null;
                                            @endphp
                                            <div class="text-sm font-medium text-blue-700">
                                                {{ $regSub->name ?? '-' }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-300">-</span>
                                        @endif
                                    </td>

                                    {{-- Room Column --}}
                                    <td class="px-4 py-3">
                                        @if($isOverride)
                                            <div class="space-y-1">
                                                <div class="line-through text-gray-400 text-xs">
                                                    {{ $regular->room ?: '-' }}
                                                </div>
                                                <div class="text-sm font-bold text-gray-800">
                                                    {{ $arr->classRoom?->room ?? ($arr->timetable?->room ?? '-') }}
                                                </div>
                                            </div>
                                        @elseif($isRelieved)
                                            <div class="line-through text-gray-400 text-sm">
                                                {{ $regular->room ?: '-' }}
                                            </div>
                                        @elseif($arr)
                                            <div class="text-sm font-bold text-gray-800">
                                                {{ $arr->classRoom?->room ?? ($arr->timetable?->room ?? '-') }}
                                            </div>
                                        @elseif($regular)
                                            <div class="text-sm text-gray-600">
                                                {{ $regular->room ?: '-' }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-300">-</span>
                                        @endif
                                    </td>

                                    {{-- Duty Status Column --}}
                                    <td class="px-4 py-3">
                                        @if($isOverride)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-amber-900 bg-amber-100 border border-amber-300 rounded-full shadow-xs">
                                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Arrangement (Override)
                                            </span>
                                        @elseif($isRelieved)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-purple-700 bg-purple-100 border border-purple-200 rounded-full">
                                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                                Relieved
                                            </span>
                                        @elseif($arr)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-blue-800 bg-blue-100 border border-blue-200 rounded-full">
                                                Arrangement Duty
                                            </span>
                                        @elseif($regular)
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full">
                                                Regular Class
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs text-gray-400 bg-gray-50 border border-gray-200/60 rounded-full">
                                                Free Period
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <h3 class="text-lg font-medium text-gray-900">Period Configuration Missing</h3>
                <p class="mt-2 text-gray-500">Please contact admin to configure school periods.</p>
            </div>
        @endif
    </div>

    {{-- Footer Summary Counter --}}
    <div class="text-xs sm:text-sm text-gray-500 text-center print:hidden flex flex-wrap items-center justify-center gap-2 pt-1">
        <span>You have {{ $timetables->count() }} regular class(es)</span>
        @if(($arrangementCount ?? 0) > 0)
            <span class="text-gray-300">•</span>
            <span class="font-bold text-amber-700">+ {{ $arrangementCount }} arrangement duty(ies)</span>
        @endif
        @if(($overrideCount ?? 0) > 0)
            <span class="text-red-500 font-semibold text-xs">({{ $overrideCount }} override)</span>
        @endif
        @if(($relievedCount ?? 0) > 0)
            <span class="text-gray-300">•</span>
            <span class="font-semibold text-purple-700">{{ $relievedCount }} period(s) relieved</span>
        @endif
        <span>on {{ $selectedDay }}.</span>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        @media print {
            body { background: white !important; }
            .glass-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
            .line-through { text-decoration: line-through !important; color: #9ca3af !important; }
        }
    </style>
</div>
