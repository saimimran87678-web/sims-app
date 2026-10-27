<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">My Schedule</h1>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">
                    {{ \App\Models\Setting::getGlobal('institute_formal_name', \App\Models\Setting::getGlobal('institute_name', 'SIMS')) }}
                </p>
                <span class="text-gray-300">•</span>
                @if($scheduleType === 'single_schedule')
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Same timetable template applies for all weekdays">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Universal Schedule (All Days)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Day-Wise Timetable
                    </span>
                @endif
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ \Carbon\Carbon::parse($targetDate)->format('D, M d, Y') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all shadow-xs">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Schedule
            </button>
        </div>
    </div>

    {{-- Arrangement Updates Notice Banner --}}
    @if(($arrangementCount ?? 0) > 0 || ($relievedCount ?? 0) > 0)
        <div class="p-4 rounded-2xl border border-amber-200 bg-amber-50/70 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:border-gray-300">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-900 flex items-center gap-2">
                        Arrangement Notice for {{ $selectedDay }} ({{ \Carbon\Carbon::parse($targetDate)->format('M d, Y') }})
                    </h4>
                    <div class="text-xs text-amber-800 mt-1 space-y-0.5">
                        @if(($arrangementCount ?? 0) > 0)
                            <p>
                                • You have <span class="font-bold">{{ $arrangementCount }} arrangement duty(ies)</span> assigned
                                @if(($overrideCount ?? 0) > 0)
                                    <span class="font-bold text-red-700">(including {{ $overrideCount }} admin override replacing your regular period)</span>
                                @endif.
                            </p>
                        @endif
                        @if(($relievedCount ?? 0) > 0)
                            <p>
                                • You are relieved from teaching in <span class="font-bold">{{ $relievedCount }} period(s)</span> (covered by substitute teachers).
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="px-3 py-1 rounded-xl bg-amber-200/80 text-amber-900 font-bold text-xs border border-amber-300">
                    {{ $arrangementCount ?? 0 }} Arrangement Duty(ies)
                </span>
            </div>
        </div>
    @endif

    {{-- Day Tabs --}}
    <div class="flex flex-wrap gap-2 print:hidden">
        @foreach($days as $day)
            @php
                $tabDate = $dayDates[$day] ?? $targetDate;
                $isTodayTab = ($tabDate === now()->format('Y-m-d'));
                $carbonDate = \Carbon\Carbon::parse($tabDate);
                $isSelected = ($selectedDay === $day);
            @endphp
            <button
                wire:click="$set('selectedDay', '{{ $day }}')"
                class="flex flex-col items-center sm:items-start px-4 py-2.5 rounded-xl font-semibold text-sm transition-all border {{ $isSelected ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200' }}"
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

    {{-- Print Header --}}
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

    {{-- Schedule Grid --}}
    <div class="glass-card rounded-2xl overflow-hidden border border-gray-200 shadow-xs">
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
                                                $regSub = \App\Models\Subject::find($regular->subject_id);
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
                                                $regSub = \App\Models\Subject::find($regular->subject_id);
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
                                                $regSub = \App\Models\Subject::find($regular->subject_id);
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
    <div class="text-sm text-gray-500 text-center print:hidden flex flex-wrap items-center justify-center gap-2">
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
        @media print {
            body { background: white !important; }
            .glass-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
            .line-through { text-decoration: line-through !important; color: #9ca3af !important; }
        }
    </style>
</div>
