<style>
    .stat-card {
        background-color: #ffffff !important;
        border: 1px solid #f1f5f9 !important;
        border-radius: 1rem !important;
        padding: 0.875rem !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 4px 12px rgba(15, 23, 42, 0.02) !important;
        transition: all 0.2s ease-in-out !important;
    }
    @media (min-width: 640px) {
        .stat-card {
            padding: 1.25rem !important;
        }
    }
    .stat-card:hover {
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06), 0 8px 24px rgba(15, 23, 42, 0.03) !important;
        transform: translateY(-1px) !important;
    }
    .dash-card {
        background-color: #ffffff !important;
        border: 1px solid #f1f5f9 !important;
        border-radius: 1rem !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 4px 12px rgba(15, 23, 42, 0.02) !important;
        padding: 1rem !important;
    }
    @media (min-width: 640px) {
        .dash-card {
            padding: 1.5rem !important;
        }
    }
    .action-btn {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        padding: 0.75rem !important;
        border-radius: 0.75rem !important;
        border: 1px solid #f1f5f9 !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        transition: all 0.15s ease-in-out !important;
    }
    @media (min-width: 640px) {
        .action-btn {
            padding: 1rem !important;
        }
    }
    .action-btn:hover {
        border-color: #e2e8f0 !important;
        background-color: #f8fafc !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
        transform: translateY(-1px) !important;
    }
    @keyframes wiggle {
        0%, 60%, 100% { transform: rotate(0deg); }
        10%  { transform: rotate(-14deg); }
        20%  { transform: rotate(12deg); }
        30%  { transform: rotate(-10deg); }
        40%  { transform: rotate(8deg); }
        50%  { transform: rotate(-6deg); }
    }
    .animate-wiggle {
        animation: wiggle 2.5s ease-in-out infinite !important;
        transform-origin: 70% 80% !important;
        display: inline-block !important;
    }
    .banner-title {
        font-size: 1.125rem !important;
    }
    @media (min-width: 640px) {
        .banner-title {
            font-size: 1.25rem !important;
        }
    }
    .financial-num {
        font-size: 0.875rem !important;
    }
    @media (min-width: 640px) {
        .financial-num {
            font-size: 1rem !important;
        }
    }
    @media (min-width: 768px) {
        .financial-num {
            font-size: 1.125rem !important;
        }
    }
    .trend-chart-svg {
        height: 120px !important;
    }
    @media (min-width: 640px) {
        .trend-chart-svg {
            height: 170px !important;
        }
    }
    .chart-date-text {
        font-size: 8px !important;
    }
    @media (min-width: 640px) {
        .chart-date-text {
            font-size: 10px !important;
        }
    }
</style>
<div x-data="{ showFinancials: false, showUnpaidModal: false }" class="space-y-6 max-w-7xl mx-auto">

    {{-- ═══ HEADER BANNER ═══════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl p-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6"
         style="background: #f8f9ff;">

        {{-- Aurora mesh color blending blobs --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            {{-- Large indigo blob top-left --}}
            <div class="absolute -top-16 -left-16 w-72 h-72 rounded-full"
                 style="background: radial-gradient(circle at 40% 40%, rgba(99,102,241,0.28) 0%, transparent 70%); filter: blur(32px);"></div>
            {{-- Violet blob top-right --}}
            <div class="absolute -top-8 right-8 w-64 h-64 rounded-full"
                 style="background: radial-gradient(circle at 60% 30%, rgba(139,92,246,0.22) 0%, transparent 65%); filter: blur(40px);"></div>
            {{-- Blue blob center --}}
            <div class="absolute top-4 left-1/2 -translate-x-1/2 w-56 h-48 rounded-full"
                 style="background: radial-gradient(circle, rgba(59,130,246,0.14) 0%, transparent 70%); filter: blur(36px);"></div>
            {{-- Pink accent bottom-right --}}
            <div class="absolute -bottom-10 right-1/4 w-48 h-48 rounded-full"
                 style="background: radial-gradient(circle, rgba(167,139,250,0.18) 0%, transparent 70%); filter: blur(30px);"></div>
            {{-- Subtle teal bottom-left --}}
            <div class="absolute -bottom-8 left-1/3 w-40 h-40 rounded-full"
                 style="background: radial-gradient(circle, rgba(20,184,166,0.10) 0%, transparent 70%); filter: blur(28px);"></div>
        </div>

        {{-- Frosted overlay for content readability --}}
        <div class="absolute inset-0 rounded-2xl" style="background: rgba(248,249,255,0.45); backdrop-filter: blur(0px);"></div>

        {{-- Left: Avatar + Greeting --}}
        <div class="flex items-center gap-4 sm:gap-5 relative z-10">
            <div class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-lg"
                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 8px 24px rgba(99,102,241,0.30);">
                <span class="text-lg sm:text-xl font-bold text-white">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</span>
                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 sm:w-4 sm:h-4 bg-emerald-400 rounded-full border-2 border-white shadow"></span>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="banner-title font-bold leading-tight" style="color: #1e1b4b;">
                        Welcome Back, {{ auth()->user()->name }}
                        <span class="inline-block animate-wiggle">👋</span>
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                          style="background: rgba(99,102,241,0.12); color: #4f46e5; border: 1px solid rgba(99,102,241,0.2);">Admin</span>
                </div>
                <p class="text-xs sm:text-sm mt-1" style="color: #6b7280;">Here is your school management overview for today.</p>
            </div>
        </div>

        {{-- Right: Info Pills --}}
        <div class="flex items-center gap-3 relative z-10 flex-wrap">
            <div class="flex flex-col items-center sm:items-end px-4 py-2.5 rounded-xl"
                 style="background: rgba(255,255,255,0.7); border: 1px solid rgba(99,102,241,0.15); box-shadow: 0 1px 4px rgba(99,102,241,0.08);">
                <p class="text-[10px] font-bold uppercase tracking-widest" style="color: #6366f1;">Active Session</p>
                <p class="text-sm font-bold mt-0.5" style="color: #1e1b4b;">
                    {{ $activeSession ? $activeSession->name : 'No Session' }}
                </p>
            </div>
            <div class="flex flex-col items-center sm:items-end px-4 py-2.5 rounded-xl"
                 style="background: rgba(255,255,255,0.7); border: 1px solid rgba(99,102,241,0.15); box-shadow: 0 1px 4px rgba(99,102,241,0.08);">
                <p class="text-[10px] font-bold uppercase tracking-widest" style="color: #6366f1;">Today</p>
                <p class="text-sm font-bold mt-0.5" style="color: #1e1b4b;">{{ \Carbon\Carbon::now()->format('d M, Y') }}</p>
            </div>
        </div>
    </div>

    {{-- ═══ STAT CARDS ══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

        {{-- Students --}}
        <div class="stat-card col-span-1">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">Active</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $stats['students'] }}</p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-1">Students</p>
        </div>

        {{-- Classes --}}
        <div class="stat-card col-span-1">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><circle cx="12" cy="9" r="2"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $stats['classes'] }}</p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-1">Classes</p>
        </div>

        {{-- Attendance --}}
        <div class="stat-card col-span-1">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <span class="text-[9px] font-bold {{ $stats['attendance'] >= 75 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50' }} px-1.5 py-0.5 rounded-full">
                    {{ $stats['attendance'] >= 75 ? 'Good' : 'Low' }}
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $stats['attendance'] }}<span class="text-base text-slate-400 font-medium">%</span></p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-1">Attendance</p>
        </div>

        {{-- Users --}}
        <div class="stat-card col-span-1">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $stats['users'] }}</p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-1">Users</p>
        </div>

        {{-- Paid This Month --}}
        @can('fees.manage')
        <div class="stat-card col-span-1">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-xl bg-teal-50 text-teal-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-teal-700">{{ $stats['paid_this_month'] }}</p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-1">Paid / Month</p>
        </div>
        @endcan

        {{-- Unpaid --}}
        @can('fees.manage')
        <div class="stat-card col-span-1">
            <div class="flex items-start justify-between mb-3">
                <div class="p-2 rounded-xl bg-rose-50 text-rose-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                </div>
                @if($stats['unpaid'] > 0)
                    <button type="button" @click="showUnpaidModal = true" 
                            class="text-[9px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 px-2 py-0.5 rounded-full transition-colors flex items-center gap-0.5 focus:outline-none"
                            title="Show Names">
                        <span>Show Names</span>
                    </button>
                @endif
            </div>
            <p class="text-2xl font-bold text-rose-500">{{ $stats['unpaid'] }}</p>
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-1">Unpaid</p>
        </div>
        @endcan
    </div>

    {{-- ═══ FINANCIALS ══════════════════════════════════════════════════════════ --}}
    @can('fees.manage')
    <div class="dash-card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-700">Financial Collection Overview</h3>
                    <button @click="showFinancials = !showFinancials" 
                            class="text-slate-400 hover:text-indigo-600 transition-colors focus:outline-none p-1 rounded-lg hover:bg-slate-100"
                            title="Toggle Balance Visibility">
                        <svg x-show="showFinancials" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="!showFinancials" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" style="display: none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Fee invoices, collections & outstanding balance</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-emerald-500"></div>
                <span class="text-xs font-bold text-emerald-600">{{ $financials['collection_rate'] }}% collected</span>
            </div>
        </div>
        {{-- Progress Bar --}}
        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mb-6">
            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500"
                 style="width: {{ min($financials['collection_rate'], 100) }}%"></div>
        </div>
        {{-- 3 columns --}}
        <div class="grid grid-cols-3 gap-2 sm:gap-4 pt-4 border-t border-slate-50">
            <div class="text-center sm:text-left">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Generated</p>
                <p class="financial-num font-bold text-slate-700 mt-1"
                   x-text="showFinancials ? 'Rs. {{ number_format($financials['generated'], 0) }}' : 'Rs. ****'">Rs. ****</p>
            </div>
            <div class="text-center border-x border-slate-100 px-2 sm:px-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Collected</p>
                <p class="financial-num font-bold text-emerald-600 mt-1"
                   x-text="showFinancials ? 'Rs. {{ number_format($financials['collected'], 0) }}' : 'Rs. ****'">Rs. ****</p>
            </div>
            <div class="text-center sm:text-right">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Outstanding</p>
                <p class="financial-num font-bold text-rose-500 mt-1"
                   x-text="showFinancials ? 'Rs. {{ number_format($financials['pending'], 0) }}' : 'Rs. ****'">Rs. ****</p>
            </div>
        </div>
    </div>
    @endcan

    {{-- ═══ MAIN GRID ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT: Chart + Distribution --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Attendance Chart --}}
            @can('students.manage')
            <div class="dash-card">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-sm font-bold text-slate-700">Attendance Trend</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Daily attendance rate over last 5 recorded days</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if(count($attendanceTrend) > 0)
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full
                                {{ collect($attendanceTrend)->avg('percentage') >= 75 ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-500 border border-rose-100' }}">
                                Avg {{ round(collect($attendanceTrend)->avg('percentage'), 1) }}%
                            </span>
                        @endif
                        <a href="{{ route('admin.attendance') }}"
                           class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 transition-colors flex items-center gap-1">
                            View All
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

                @if(count($attendanceTrend) > 0)
                    @php
                        // Build smooth cubic bezier path — coordinates from controller:
                        // x: evenly from 40→470, y: 118 (0%) → 18 (100%), baseline y=128
                        $pts    = $chartPoints;
                        $cnt    = count($pts);
                        $smooth = '';
                        if ($cnt > 0) {
                            $smooth = "M {$pts[0]['x']} {$pts[0]['y']}";
                            for ($i = 1; $i < $cnt; $i++) {
                                $cpx = $pts[$i-1]['x'] + ($pts[$i]['x'] - $pts[$i-1]['x']) * 0.5;
                                $smooth .= " C $cpx {$pts[$i-1]['y']}, $cpx {$pts[$i]['y']}, {$pts[$i]['x']} {$pts[$i]['y']}";
                            }
                        }
                        $firstX     = $pts[0]['x'] ?? 255;
                        $lastX      = $pts[$cnt - 1]['x'] ?? 255;
                        $smoothFill = $cnt > 0 ? ($smooth . " L $lastX 128 L $firstX 128 Z") : '';
                        // Grid lines: 25%→y=93, 50%→y=68, 75%→y=43, 100%→y=18
                        $gridLines = [
                            ['pct' => 25,  'y' => 93],
                            ['pct' => 50,  'y' => 68],
                            ['pct' => 75,  'y' => 43],
                            ['pct' => 100, 'y' => 18],
                        ];
                    @endphp

                    <div class="relative mt-2">
                        <svg class="w-full trend-chart-svg" viewBox="0 0 500 150" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%"   stop-color="#6366f1" stop-opacity="0.20"/>
                                    <stop offset="80%"  stop-color="#6366f1" stop-opacity="0.03"/>
                                    <stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
                                </linearGradient>
                                <linearGradient id="lineGrad" x1="0" y1="0" x2="1" y2="0">
                                    <stop offset="0%"   stop-color="#4f46e5"/>
                                    <stop offset="50%"  stop-color="#7c3aed"/>
                                    <stop offset="100%" stop-color="#6d28d9"/>
                                </linearGradient>
                                <filter id="dotGlow" x="-60%" y="-60%" width="220%" height="220%">
                                    <feGaussianBlur in="SourceGraphic" stdDeviation="3" result="blur"/>
                                    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                                </filter>
                            </defs>

                            {{-- Y-axis grid lines (aligned to 18–118 range) --}}
                            @foreach($gridLines as $gl)
                                <line x1="32" x2="490" y1="{{ $gl['y'] }}" y2="{{ $gl['y'] }}"
                                      stroke="{{ $gl['pct'] === 100 ? '#e9ecef' : '#f3f4f6' }}"
                                      stroke-width="1"
                                      stroke-dasharray="{{ $gl['pct'] === 100 ? '' : '5 3' }}"/>
                                <text x="28" y="{{ $gl['y'] + 3.5 }}"
                                      fill="#c9d1db" font-size="8.5" text-anchor="end"
                                      font-family="Inter,sans-serif">{{ $gl['pct'] }}</text>
                            @endforeach

                            {{-- Baseline --}}
                            <line x1="32" x2="490" y1="128" y2="128" stroke="#dde1e7" stroke-width="1.5"/>

                            {{-- Filled area under curve --}}
                            @if($smoothFill)
                                <path d="{{ $smoothFill }}" fill="url(#areaGrad)"/>
                            @endif

                            {{-- Smooth bezier line --}}
                            @if($smooth)
                                <path d="{{ $smooth }}" fill="none"
                                      stroke="url(#lineGrad)" stroke-width="2.8"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            @endif

                            {{-- Data point dots --}}
                            @foreach($chartPoints as $pt)
                                {{-- Soft glow ring --}}
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="9"
                                        fill="#6366f1" opacity="0.08"/>
                                {{-- White core dot --}}
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="5"
                                        fill="#ffffff" stroke="#4f46e5" stroke-width="2.5"
                                        filter="url(#dotGlow)"/>
                                {{-- Value label floating above dot --}}
                                <text x="{{ $pt['x'] }}" y="{{ max(12, $pt['y'] - 12) }}"
                                      fill="#4f46e5" font-size="9" text-anchor="middle"
                                      font-weight="700" font-family="Inter,sans-serif">{{ $pt['percentage'] }}%</text>

                                {{-- Date label directly below baseline --}}
                                <text x="{{ $pt['x'] }}" y="143"
                                      fill="#94a3b8" font-size="9.5" text-anchor="middle"
                                      font-weight="500" font-family="Inter,sans-serif">{{ $pt['date'] }}</text>
                            @endforeach
                        </svg>
                    </div>

                    {{-- Bottom Legend --}}
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-50">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-0.5 rounded-full bg-gradient-to-r from-indigo-500 to-violet-600"></div>
                            <span class="text-[10px] font-medium text-slate-400">Attendance %</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span class="text-[10px] text-slate-400">≥75% Good</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                <span class="text-[10px] text-slate-400">&lt;75% Low</span>
                            </div>
                        </div>
                    </div>

                @else
                    <div class="h-40 flex flex-col items-center justify-center rounded-xl gap-3"
                         style="background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%); border: 1.5px dashed #c7d2fe;">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><polyline points="9 16 11 18 15 14"/></svg>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-semibold text-slate-600">No Attendance Recorded</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Start marking daily registers to see the trend chart</p>
                        </div>
                        <a href="{{ route('admin.attendance') }}"
                           class="text-[11px] font-bold text-white px-4 py-1.5 rounded-lg transition-all hover:opacity-90"
                           style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                            Mark Attendance
                        </a>
                    </div>
                @endif
            </div>
            @endcan

            {{-- Class Distribution --}}
            @can('classes.manage')
            <div class="dash-card">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-700">Class Enrolment Strength</h3>
                    <a href="{{ route('admin.classes') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">View All →</a>
                </div>
                @if(count($classDistribution) > 0)
                    @php $maxStudents = $classDistribution->max('students_count') ?: 1; @endphp
                    <div class="space-y-4">
                        @foreach($classDistribution as $cls)
                            @php $pct = round(($cls->students_count / $maxStudents) * 100); @endphp
                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <span class="text-xs font-semibold text-slate-700">{{ $cls->name }}</span>
                                    <span class="text-[11px] font-bold text-slate-500">{{ $cls->students_count }} {{ Str::plural('student', $cls->students_count) }}</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-400 to-indigo-600 rounded-full transition-all duration-500"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 text-center py-4">No class enrollment data yet.</p>
                @endif
            </div>
            @endcan
        </div>

        {{-- RIGHT: Quick Actions + Activity --}}
        <div class="space-y-6">

            {{-- Quick Actions --}}
            @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('students.manage') || auth()->user()->can('classes.manage') || auth()->user()->can('fees.manage') || auth()->user()->can('reports.view'))
            <div class="dash-card">
                <h3 class="text-sm font-bold text-slate-700 mb-1">Quick Actions</h3>
                <p class="text-[11px] text-slate-400 mb-5">Immediate management shortcuts</p>
                <div class="grid grid-cols-2 gap-3">
                    @can('students.manage')
                    <a href="{{ route('admin.students', ['open_add_modal' => 1]) }}" class="action-btn">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="16" x2="22" y1="11" y2="11"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600">Admit Student</span>
                    </a>
                    @endcan
                    @can('classes.manage')
                    <a href="{{ route('admin.classes', ['open_add_modal' => 1]) }}" class="action-btn">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600">Classes</span>
                    </a>
                    @endcan
                    @can('students.manage')
                    <a href="{{ route('admin.attendance') }}" class="action-btn">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600">Attendance</span>
                    </a>
                    @endcan
                    @can('fees.manage')
                    <a href="{{ route('admin.fee.record-payment') }}" class="action-btn">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600">Record Fee</span>
                    </a>
                    @endcan
                    @can('students.manage')
                    <a href="{{ route('admin.whatsapp-setup') }}" class="action-btn">
                        <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600">WhatsApp</span>
                    </a>
                    @endcan
                    @can('reports.view')
                    <a href="{{ route('admin.reports') }}" class="action-btn">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600">Reports</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endif

            {{-- Activity Feed --}}
            @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('students.manage') || auth()->user()->can('fees.manage'))
            <div class="dash-card">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-700">Recent Activity</h3>
                    @if(count($activityFeed) > 0)
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    @endif
                </div>

                @if(count($activityFeed) > 0)
                    <div class="relative pl-5 ml-1 border-l-2 border-slate-100 space-y-4">
                        @foreach($activityFeed as $act)
                            <div class="relative">
                                <span class="absolute -left-[22px] top-1 w-3 h-3 rounded-full border-2 border-white
                                    {{ $act['color'] === 'emerald' ? 'bg-emerald-500' : 'bg-blue-500' }}"></span>
                                <p class="text-xs font-bold text-slate-700 leading-tight">{{ $act['title'] }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $act['description'] }}</p>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase">{{ $act['meta'] }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $act['time']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-6 gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/></svg>
                        </div>
                        <p class="text-xs text-slate-400 text-center">No activity yet.<br>Start by recording attendance or a fee payment.</p>
                    </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- ═══ UNPAID STUDENTS MODAL ═══════════════════════════════════════════════ --}}
    <div x-show="showUnpaidModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;">
         
        <div @click.outside="showUnpaidModal = false" 
             x-show="showUnpaidModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95 translate-y-4"
             x-transition:enter-end="scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="scale-100 translate-y-0"
             x-transition:leave-end="scale-95 translate-y-4"
             class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden border border-slate-100">
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Unpaid Students</h3>
                    <p class="text-xs text-slate-400 mt-0.5">List of students with outstanding dues for {{ date('F Y') }}</p>
                </div>
                <button @click="showUnpaidModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1.5 rounded-lg hover:bg-slate-50 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Content --}}
            <div class="p-6 max-h-[60vh] overflow-y-auto space-y-6">
                @forelse($unpaidStudents as $className => $students)
                    <div class="space-y-2">
                        {{-- Class Header --}}
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-1">
                            <span class="w-1.5 h-3 bg-rose-500 rounded-full"></span>
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ $className }}</h4>
                            <span class="text-[10px] font-semibold text-slate-400">({{ $students->count() }} {{ Str::plural('student', $students->count()) }})</span>
                        </div>

                        {{-- Student List --}}
                        <div class="divide-y divide-slate-50 bg-slate-50/50 rounded-xl border border-slate-100 overflow-hidden">
                            @foreach($students as $student)
                                <div class="flex items-center justify-between px-4 py-3 hover:bg-white transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-slate-400 w-5">#{{ $student->roll_no }}</span>
                                        <span class="text-sm font-semibold text-slate-700">{{ $student->name }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">Unpaid</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-8 gap-3">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-slate-700">All Fees Collected!</p>
                            <p class="text-xs text-slate-400 mt-0.5">No students are currently unpaid for this month.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button @click="showUnpaidModal = false" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold shadow-sm transition-colors focus:outline-none">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
