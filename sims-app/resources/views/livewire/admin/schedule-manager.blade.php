<div class="space-y-6" x-data="{ 
    showPrintModal: false, 
    activePrintTab: 'master', 
    selectedPrintClassId: '{{ data_get(collect($classes)->first(), 'id', '') }}', 
    selectedPrintTeacherId: '{{ data_get(collect($teachers)->first(), 'id', '') }}' 
}">
    <div class="flex justify-between items-center">
        <div class="flex items-start gap-4">
            <x-schedule-menu />
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Schedule Management</h1>
                <p class="text-gray-500">Assign teachers to classes for each period</p>
            </div>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700">{{ session('message') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-xl text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Day Tabs + View Toggle --}}
    <div class="flex flex-wrap gap-2 justify-between items-center border-b border-gray-200 pb-3">
        {{-- Left: Day Tabs / Single Schedule Status --}}
        @if($scheduleType === 'single_schedule')
            <div class="flex items-center gap-2.5 py-1">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold text-sm bg-blue-50 text-blue-800 border border-blue-200 shadow-xs">
                    <span class="text-base">🗓️</span>
                    <span>Single Universal Schedule</span>
                </span>
                <span class="text-xs text-gray-500 font-medium">
                    (Unified timetable for all {{ count($days) }} active school days)
                </span>
            </div>
        @else
            <div class="flex gap-2 items-center flex-wrap">
                @foreach($days as $day)
                    <button
                        wire:click="$set('selectedDay', '{{ $day }}')"
                        class="px-4 py-2 rounded-t-lg font-medium transition-colors {{ $selectedDay === $day ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    >
                     {{ substr($day, 0, 3) }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Right: View Toggle + Action Buttons --}}
        <div class="flex gap-2 items-center flex-wrap">
            {{-- View Mode Toggle --}}
            <div class="flex items-center bg-gray-100 rounded-lg p-0.5 gap-0.5" role="group" aria-label="Grid view mode">
                <button
                    wire:click="$set('viewMode', 'class')"
                    id="view-toggle-class"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-150
                        {{ $viewMode === 'class'
                            ? 'bg-white text-blue-700 shadow-sm ring-1 ring-blue-200'
                            : 'text-gray-500 hover:text-gray-700' }}"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M3 14h18M10 3v18M14 3v18"/>
                    </svg>
                    By Class
                </button>
                <button
                    wire:click="$set('viewMode', 'teacher')"
                    id="view-toggle-teacher"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-150
                        {{ $viewMode === 'teacher'
                            ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-indigo-200'
                            : 'text-gray-500 hover:text-gray-700' }}"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    By Teacher
                </button>
            </div>

            <div class="w-px h-6 bg-gray-300"></div>

            <button
                wire:click="syncAllocations"
                class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-1.5 shadow-sm font-medium"
                title="Synchronize all timetable entries into Teacher Gradebook and User Management"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync to Gradebook
            </button>

            <button
                type="button"
                @click="showPrintModal = true"
                class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-1.5 shadow-sm font-medium"
                title="Print Master, Class, and Teacher Timetables"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Timetables
            </button>
            @if($scheduleType !== 'single_schedule')
                <button
                    wire:click="copyToAllDays"
                    class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    Copy to All
                </button>
                <button
                    wire:click="clearDay"
                    class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Day
                </button>
            @else
                <button
                    wire:click="clearDay"
                    class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Schedule
                </button>
            @endif
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- CLASS VIEW GRID                                              --}}
    {{-- =========================================================== --}}
    @if($viewMode === 'class')
    <div class="glass-card rounded-2xl overflow-hidden border border-gray-200/80 shadow-xs bg-white">
        {{-- Matching Cohesive Top Legend Bar --}}
        <div class="px-4 py-2.5 bg-blue-50/60 border-b border-blue-100 flex flex-wrap items-center justify-between gap-3 text-xs text-blue-800">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span><strong>Class View</strong> — rows are classes, columns are periods. Click any cell to assign or edit a period schedule.</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Role Notches --}}
                <span class="inline-flex items-center gap-1.5 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200 text-amber-900 font-semibold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500 ring-2 ring-amber-200 flex-shrink-0"></span>
                    <span>Has Class Teacher</span>
                </span>

                <div class="h-4 w-px bg-blue-200 mx-0.5"></div>

                {{-- Status Indicators (Uniform pill styling with dedicated padding and swatches) --}}
                <span class="inline-flex items-center gap-1.5 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200 text-blue-800 font-medium shadow-xs">
                    <span class="w-2.5 h-2.5 rounded bg-blue-200 border border-blue-400 flex-shrink-0"></span>
                    <span>Assigned</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 text-emerald-800 font-medium shadow-xs">
                    <span class="w-2.5 h-2.5 rounded bg-emerald-200 border border-emerald-400 flex-shrink-0"></span>
                    <span>Free</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-200 text-purple-800 font-medium shadow-xs">
                    <span class="w-2.5 h-2.5 rounded bg-purple-200 border border-purple-400 flex-shrink-0"></span>
                    <span>Divided</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-200 text-teal-800 font-medium shadow-xs">
                    <span class="w-2.5 h-2.5 rounded bg-teal-200 border border-teal-400 flex-shrink-0"></span>
                    <span>Merged</span>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-50/60">
                    <tr>
                        <th class="px-3.5 py-2.5 text-left text-xs font-bold text-gray-700 uppercase w-40 sticky left-0 bg-gray-50 z-20 border-r border-gray-100">
                            Class
                        </th>
                        @foreach($periods as $period)
                            <th class="px-1.5 py-2 text-center text-xs font-medium 
                                {{ $period->is_break ? 'bg-yellow-50/70 text-yellow-800 w-16 min-w-[62px]' : ($period->is_assembly ? 'bg-purple-50/70 text-purple-800 w-16 min-w-[62px]' : 'text-gray-600 min-w-[105px]') }}">
                                <div class="font-bold text-xs leading-tight">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 font-normal whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i') }}–{{ \Carbon\Carbon::parse($period->end_time)->format('h:i') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($classes as $class)
                        @php
                            $classId = data_get($class, 'id');
                            $className = data_get($class, 'name');
                            $ctName = $classTeacherMap[$classId] ?? data_get($class, 'class_teacher_name', null);
                            $hasCt = !empty($ctName);
                        @endphp
                        <tr class="hover:bg-blue-50/20 transition-colors">
                            {{-- Class Identifier Cell with Persistent CT Notch --}}
                            <td class="px-3.5 py-2.5 sticky left-0 bg-white z-10 group/classheader border-r border-gray-100 {{ $hasCt ? 'bg-amber-50/20' : '' }}">
                                <div class="flex items-center justify-between gap-1.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $hasCt ? 'bg-amber-500 ring-2 ring-amber-100 shadow-2xs' : 'bg-gray-300' }}" title="{{ $hasCt ? 'Class Teacher: ' . $ctName : 'No Class Teacher Assigned' }}"></span>
                                        <div class="text-sm font-bold text-gray-800 truncate max-w-[110px]" title="{{ $className }}">
                                            {{ $className }}
                                        </div>
                                    </div>
                                    <a 
                                        href="/admin/schedule/print/class/{{ $classId }}?autoprint=1" 
                                        target="_blank" 
                                        class="opacity-0 group-hover/classheader:opacity-100 transition-opacity p-1 text-gray-400 hover:text-indigo-600 rounded hover:bg-indigo-50" 
                                        title="Print Class {{ $className }} Timetable"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </a>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-1 text-[10px]">
                                    @if($hasCt)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-semibold text-amber-800 bg-amber-50 border border-amber-200/90 truncate max-w-[125px]" title="Class Teacher: {{ $ctName }}">
                                            <span class="truncate">CT: {{ $ctName }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-medium text-gray-400 bg-gray-50 border border-gray-100 truncate text-[10px]" title="No Class Teacher Assigned">
                                            <span>No CT Assigned</span>
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Period Cells --}}
                            @foreach($periods as $period)
                                @if($period->is_break)
                                    <td class="px-1.5 py-2 bg-yellow-50/40 text-center border-l border-gray-100">
                                        <span class="text-yellow-700 font-bold text-[10px] tracking-wider uppercase">Break</span>
                                    </td>
                                @elseif($period->is_assembly)
                                    <td class="px-1.5 py-2 bg-purple-50/40 text-center border-l border-gray-100">
                                        <span class="text-purple-700 font-bold text-[10px] tracking-wider uppercase">Assembly</span>
                                    </td>
                                @else
                                    @php $schedules = $this->getSchedule($classId, $period->period_no); @endphp
                                    <td
                                        wire:click="openModal({{ $classId }}, {{ $period->period_no }})"
                                        class="px-2 py-1.5 cursor-pointer border-l border-gray-100 {{ $schedules->isNotEmpty() ? 'hover:bg-blue-50/80' : 'hover:bg-emerald-50/70' }} transition-all group align-middle"
                                        title="{{ $schedules->isNotEmpty() ? 'Edit assignment for ' . $className . ' in ' . $period->label : 'Assign period for ' . $className . ' in ' . $period->label }}"
                                    >
                                        @if($schedules->isNotEmpty())
                                            <div class="flex flex-col gap-1.5">
                                                @php
                                                    $groupedSchedules = $schedules->groupBy('teacher_id');
                                                @endphp
                                                @foreach($groupedSchedules as $tId => $groupRows)
                                                    @php
                                                        $teacher = collect($teachers)->firstWhere('id', $tId);
                                                        $groupSubjects = $groupRows->map(fn($r) => \App\Models\Subject::find($r->subject_id))->filter();
                                                        $firstRow = $groupRows->first();
                                                        $partnerLabel = $mergedPartnerClassesMap[$firstRow->id] ?? null;
                                                    @endphp
                                                    <div class="text-xs space-y-0.5 {{ $loop->index > 0 ? 'border-t border-gray-200 pt-1' : '' }}">
                                                        <div class="font-bold text-blue-700 leading-tight flex items-center flex-wrap gap-1">
                                                            @if($groupSubjects->count() > 1)
                                                                @foreach($groupSubjects as $sIdx => $sub)
                                                                    @if($sIdx > 0)
                                                                        <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-blue-100 text-blue-700 text-[10px] font-black leading-none shadow-2xs" title="Multi-subject">+</span>
                                                                    @endif
                                                                    <span class="px-1 py-0.2 rounded bg-blue-50 text-blue-800 text-[11px] font-bold border border-blue-200/60" title="{{ $sub->name }}">{{ $sub->abbreviation }}</span>
                                                                @endforeach
                                                            @elseif($groupSubjects->isNotEmpty())
                                                                <span class="truncate" title="{{ $groupSubjects->first()?->name }}">{{ $groupSubjects->first()?->name }}</span>
                                                            @else
                                                                <span class="text-gray-400">-</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-gray-500 text-[11px] truncate leading-tight">{{ $teacher->name ?? '-' }}</div>
                                                        @if($firstRow->is_merged && $partnerLabel)
                                                            <span class="text-[9px] text-teal-700 bg-teal-50 px-1 py-0.2 rounded border border-teal-200 inline-block leading-tight" title="Merged with {{ $partnerLabel }}">🔗 +{{ $partnerLabel }}</span>
                                                        @endif
                                                        @if($firstRow->is_divided && $loop->last)
                                                            <span class="text-[9px] text-purple-700 bg-purple-50 px-1 py-0.2 rounded border border-purple-200 inline-block leading-tight">Divided</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center h-full py-1">
                                                <span class="text-[10px] text-emerald-600 bg-emerald-50 group-hover:bg-emerald-100 group-hover:text-emerald-700 px-2 py-0.5 rounded-md font-semibold transition-all flex items-center gap-1 border border-emerald-200/60 shadow-2xs">
                                                    <span class="text-xs font-bold leading-none">+</span> Assign
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- TEACHER VIEW GRID                                            --}}
    {{-- =========================================================== --}}
    @if($viewMode === 'teacher')
    <div class="glass-card rounded-2xl overflow-hidden">
        {{-- Legend --}}
        <div class="px-4 py-2.5 bg-indigo-50/60 border-b border-indigo-100 flex flex-wrap items-center justify-between gap-3 text-xs text-indigo-700">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><strong>Teacher View</strong> — click any cell to assign or edit a period.</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Role Notches --}}
                <span class="inline-flex items-center gap-1.5 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200 text-amber-900 font-semibold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500 ring-2 ring-amber-200 flex-shrink-0"></span>
                    <span>Class Teacher</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200 text-blue-900 font-semibold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-blue-500 ring-2 ring-blue-200 flex-shrink-0"></span>
                    <span>Subject Teacher</span>
                </span>

                <div class="h-4 w-px bg-indigo-200 mx-0.5"></div>

                {{-- Status Indicators (Uniform pill styling with dedicated padding and swatches) --}}
                <span class="inline-flex items-center gap-1.5 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200 text-indigo-800 font-medium shadow-xs">
                    <span class="w-2.5 h-2.5 rounded bg-indigo-200 border border-indigo-400 flex-shrink-0"></span>
                    <span>Assigned</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 text-emerald-800 font-medium shadow-xs">
                    <span class="w-2.5 h-2.5 rounded bg-emerald-200 border border-emerald-400 flex-shrink-0"></span>
                    <span>Free</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-200 text-purple-800 font-medium shadow-xs">
                    <span class="w-2.5 h-2.5 rounded bg-purple-200 border border-purple-400 flex-shrink-0"></span>
                    <span>Divided</span>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase w-44 sticky left-0 bg-gray-50 z-20">Teacher</th>
                        @foreach($periods as $period)
                            <th class="px-2 py-3 text-center text-xs font-medium min-w-[130px]
                                {{ $period->is_break ? 'bg-yellow-50 text-yellow-700' : ($period->is_assembly ? 'bg-purple-50 text-purple-700' : 'text-gray-500') }}">
                                <div class="font-bold">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i') }}–{{ \Carbon\Carbon::parse($period->end_time)->format('h:i') }}
                                </div>
                            </th>
                        @endforeach
                        <th class="px-3 py-3 text-center text-xs font-bold text-indigo-800 uppercase bg-indigo-50/50 border-l border-indigo-100 min-w-[95px] sticky right-0">
                            <div>Total</div>
                            <div class="text-[10px] text-indigo-500 font-medium lowercase">Periods</div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($teachers as $teacher)
                        @php
                            $ctClassName = $teacherClassMap[$teacher->id] ?? null;
                            $isClassTeacher = !empty($ctClassName);
                            $teacherPeriodCount = isset($teacherGridMap[$teacher->id])
                                ? count($teacherGridMap[$teacher->id])
                                : 0;
                        @endphp
                        <tr class="hover:bg-indigo-50/20 transition-colors">
                            {{-- Teacher Name Cell with Colored Notch & CT / Subject Teacher Badge --}}
                            <td class="px-3.5 py-2.5 sticky left-0 bg-white z-10 group/teacherheader border-r border-gray-100 {{ $isClassTeacher ? 'bg-amber-50/20' : '' }}">
                                <div class="flex items-center justify-between gap-1.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $isClassTeacher ? 'bg-amber-500 ring-2 ring-amber-100 shadow-2xs' : 'bg-blue-400' }}" title="{{ $isClassTeacher ? 'Class Teacher (' . $ctClassName . ')' : 'Subject Teacher' }}"></span>
                                        <div class="text-sm font-bold text-gray-800 truncate max-w-[120px]" title="{{ $teacher->name }}">
                                            {{ $teacher->name }}
                                        </div>
                                    </div>
                                    <a 
                                        href="/admin/schedule/print/teacher/{{ $teacher->id }}?autoprint=1" 
                                        target="_blank" 
                                        class="opacity-0 group-hover/teacherheader:opacity-100 transition-opacity p-1 text-gray-400 hover:text-indigo-600 rounded hover:bg-indigo-50" 
                                        title="Print Teacher {{ $teacher->name }} Slip"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </a>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-1 text-[10px]">
                                    @if($isClassTeacher)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-semibold text-amber-800 bg-amber-50 border border-amber-200/90 truncate max-w-[105px]" title="Class Teacher of {{ $ctClassName }}">
                                            <span class="truncate">CT: {{ $ctClassName }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-medium text-blue-700 bg-blue-50 border border-blue-100 truncate" title="Subject Teacher only">
                                            <span>Subject Teacher</span>
                                        </span>
                                    @endif
                                    <span class="text-gray-400 font-medium whitespace-nowrap ml-auto">
                                        {{ $teacherPeriodCount }}p
                                    </span>
                                </div>
                            </td>

                            {{-- Period Cells --}}
                            @foreach($periods as $period)
                                @if($period->is_break)
                                    <td class="px-2 py-3 bg-yellow-50/50 text-center">
                                        <span class="text-yellow-500 text-xs">Break</span>
                                    </td>
                                @elseif($period->is_assembly)
                                    <td class="px-2 py-3 bg-purple-50/50 text-center">
                                        <span class="text-purple-500 text-xs">Assembly</span>
                                    </td>
                                @else
                                    @php
                                        $cellRows = $teacherGridMap[$teacher->id][$period->period_no] ?? [];
                                    @endphp

                                    @if(count($cellRows) > 0)
                                        {{-- Assigned Cell — clickable to edit the assignment --}}
                                        <td
                                            wire:click="openModal({{ $cellRows[0]->class_id }}, {{ $period->period_no }}, {{ $teacher->id }})"
                                            class="px-2 py-2 cursor-pointer border-l border-gray-100 hover:bg-indigo-50/80 transition-all"
                                            title="Edit assignment for {{ $teacher->name }} in {{ $period->label }}"
                                        >
                                            <div class="flex flex-col gap-1.5">
                                                @php
                                                    $groupedByClass = collect($cellRows)->groupBy('class_id');
                                                @endphp
                                                @foreach($groupedByClass as $cId => $classRows)
                                                    @php
                                                        $classObj = $classes->firstWhere('id', $cId);
                                                        $isTeachingHomeClass = ($isClassTeacher && $classObj && $classObj->name === $ctClassName);
                                                        $cSubjects = $classRows->map(fn($r) => \App\Models\Subject::find($r->subject_id))->filter();
                                                        $firstCRow = $classRows->first();
                                                    @endphp
                                                    <div class="text-xs {{ $loop->index > 0 ? 'border-t border-indigo-100 pt-1' : '' }}">
                                                        <div class="flex items-center justify-between gap-1">
                                                            <div class="font-bold {{ $isTeachingHomeClass ? 'text-amber-900' : 'text-indigo-700' }} truncate">
                                                                {{ $classObj->name ?? ('Class #'.$cId) }}
                                                            </div>
                                                            @if($isTeachingHomeClass)
                                                                <span class="text-[9px] font-bold text-amber-700 bg-amber-100/80 border border-amber-200 px-1 py-0.2 rounded-sm" title="Teaching their assigned homeroom class">CT</span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center flex-wrap gap-1 mt-0.5 leading-tight">
                                                            @if($cSubjects->count() > 1)
                                                                @foreach($cSubjects as $sIdx => $sub)
                                                                    @if($sIdx > 0)
                                                                        <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-black leading-none shadow-2xs" title="Multi-subject">+</span>
                                                                    @endif
                                                                    <span class="px-1 py-0.2 rounded bg-indigo-50 text-indigo-800 text-[10.5px] font-bold border border-indigo-200/60" title="{{ $sub->name }}">{{ $sub->abbreviation }}</span>
                                                                @endforeach
                                                            @elseif($cSubjects->isNotEmpty())
                                                                <span class="truncate text-gray-700 font-medium" title="{{ $cSubjects->first()?->name }}">{{ $cSubjects->first()?->name }}</span>
                                                            @else
                                                                <span class="text-gray-400">—</span>
                                                            @endif
                                                        </div>
                                                        @if($firstCRow->is_divided)
                                                            <span class="text-[10px] text-purple-600 bg-purple-50 px-1 rounded">Divided</span>
                                                        @endif
                                                        @if($firstCRow->is_merged && ($mergedPartnerClassesMap[$firstCRow->id] ?? null))
                                                            <span class="text-[10px] text-teal-700 bg-teal-50 px-1 rounded">🔗 +{{ $mergedPartnerClassesMap[$firstCRow->id] }}</span>
                                                        @endif
                                                        @if($firstCRow->room)
                                                            <div class="text-[10px] text-gray-400">{{ $firstCRow->room }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    @else
                                        {{-- Free Cell — clickable to assign a class to this teacher --}}
                                        <td
                                            wire:click="openModal(null, {{ $period->period_no }}, {{ $teacher->id }})"
                                            class="px-2 py-2 cursor-pointer border-l border-gray-100 hover:bg-emerald-50/70 transition-all group"
                                            title="Assign class for {{ $teacher->name }} in {{ $period->label }}"
                                        >
                                            <div class="flex items-center justify-center h-full">
                                                <span class="text-[11px] text-emerald-600 bg-emerald-50 group-hover:bg-emerald-100 group-hover:text-emerald-700 px-2.5 py-1 rounded-lg font-semibold transition-all flex items-center gap-1 border border-emerald-200/60 shadow-xs">
                                                    <span class="text-xs font-bold leading-none">+</span> Assign
                                                </span>
                                            </div>
                                        </td>
                                    @endif
                                @endif
                            @endforeach

                            {{-- Total Assigned Periods Column --}}
                            <td class="px-3 py-2 text-center border-l border-indigo-100/70 bg-indigo-50/10 sticky right-0">
                                <span class="inline-flex items-center justify-center min-w-[32px] h-7 px-2.5 rounded-full text-xs font-bold transition-all {{ $teacherPeriodCount > 0 ? 'bg-indigo-100 text-indigo-800 border border-indigo-200 shadow-xs' : 'bg-gray-100 text-gray-400' }}" title="{{ $teacher->name }}: {{ $teacherPeriodCount }} assigned period{{ $teacherPeriodCount !== 1 ? 's' : '' }}">
                                    {{ $teacherPeriodCount }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($periods) + 2 }}" class="px-6 py-10 text-center text-gray-400">
                                No teachers found for the selected session/shift.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- ASSIGNMENT MODAL                                             --}}
    {{-- =========================================================== --}}
    @if($showModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit Period Assignment' : 'Assign Period' }}</h2>
                        <p class="text-sm text-gray-500">
                            @if($modalClassId && collect($classes)->firstWhere('id', $modalClassId))
                                <span class="font-semibold text-gray-700">{{ data_get(collect($classes)->firstWhere('id', $modalClassId), 'name') }}</span> •
                            @elseif($selectedTeacherId && collect($teachers)->firstWhere('id', $selectedTeacherId))
                                <span class="font-semibold text-indigo-700">{{ data_get(collect($teachers)->firstWhere('id', $selectedTeacherId), 'name') }}</span> •
                            @endif
                            @if($scheduleType === 'single_schedule')
                                <span class="text-blue-600 font-medium">All School Days</span>
                            @else
                                {{ $selectedDay }}
                            @endif
                            • {{ $modalPeriodLabel }}
                        </p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @if(session()->has('error'))
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs p-3 rounded-xl flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <div class="space-y-4">
                    {{-- Class Selection (Selectable in Teacher View, or whenever class not yet selected) --}}
                    @if($viewMode === 'teacher' || empty($modalClassId))
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Class <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="modalClassId" class="w-full px-4 py-2 rounded-xl border {{ empty($modalClassId) ? 'border-amber-400 ring-2 ring-amber-100' : 'border-gray-200' }} focus:ring-2 focus:ring-blue-500 outline-none bg-white font-semibold text-gray-800">
                                <option value="">-- Select Class --</option>
                                @foreach($classes as $c)
                                    @php
                                        $isClassBusy = in_array($c->id, $this->busyClassIds);
                                    @endphp
                                    <option value="{{ $c->id }}">
                                        {{ $c->name }}{{ $isClassBusy && $c->id != $modalClassId ? ' (Busy in this period)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if(empty($modalClassId))
                                <p class="text-xs text-amber-600 mt-1 font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Please select a class to load its available subjects
                                </p>
                            @endif
                        </div>

                        {{-- Friendly Conflict & Replacement Notification (Teacher View Only) --}}
                        @if($viewMode === 'teacher' && $classConflictNotice)
                            <div class="p-3.5 bg-amber-50/90 border border-amber-200/90 rounded-xl text-xs text-amber-900 flex items-start gap-2.5 shadow-xs">
                                <div class="w-5 h-5 rounded-full bg-amber-200/80 text-amber-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div class="leading-relaxed flex-1">
                                    <div class="font-bold text-amber-950 text-[12px]">Schedule Overwrite Notice:</div>
                                    <div class="mt-0.5 text-amber-900">
                                        <strong>{{ $classConflictNotice['class_name'] }}</strong> is already assigned to <strong>{{ $classConflictNotice['teacher_name'] }}</strong> for <em>{{ $classConflictNotice['subject_name'] }}</em> in {{ $classConflictNotice['period_label'] }}.
                                    </div>
                                    <div class="mt-1 text-amber-700 font-medium">
                                        Saving this assignment will replace that existing class allocation.
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Teacher Selection: In Teacher View, Teacher is already known; show verification card instead of dropdown --}}
                    @if($viewMode === 'teacher')
                        <input type="hidden" wire:model="selectedTeacherId" />
                        @php
                            $currentTeacherObj = collect($teachers)->firstWhere('id', $selectedTeacherId);
                            $modalTeacherCtClass = $teacherClassMap[$selectedTeacherId] ?? null;
                            $modalTeacherIsCt = !empty($modalTeacherCtClass);
                        @endphp
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Assigned Teacher (Verified)
                            </label>
                            <div class="p-3 {{ $modalTeacherIsCt ? 'bg-amber-50/70 border-amber-200/90' : 'bg-indigo-50/70 border-indigo-200/80' }} border rounded-xl flex items-center justify-between shadow-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg {{ $modalTeacherIsCt ? 'bg-amber-600' : 'bg-indigo-600' }} text-white flex items-center justify-center font-bold text-sm shadow-xs">
                                        {{ substr($currentTeacherObj?->name ?? 'T', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 leading-tight">
                                            {{ $currentTeacherObj?->name ?? 'Selected Teacher' }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            @if($modalTeacherIsCt)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100 px-1.5 py-0.2 rounded border border-amber-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Class Teacher ({{ $modalTeacherCtClass }})
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[10px] font-medium text-blue-700 bg-blue-100 px-1.5 py-0.2 rounded border border-blue-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Subject Teacher
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200/70">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Confirmed
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Class View: Standard Teacher Dropdown --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Teacher <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="selectedTeacherId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white font-medium text-gray-800">
                                <option value="">Select Teacher</option>
                                @foreach($availableTeachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Only shows teachers not busy this period</p>
                        </div>
                    @endif

                    {{-- Optional Class Teacher Assignment --}}
                    @if($selectedTeacherId && $modalClassId)
                        <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-3 transition-all">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" wire:model.live="setAsClassTeacher" class="mt-0.5 w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" />
                                <div class="flex-1 text-xs">
                                    <span class="font-semibold text-amber-900 block text-sm">
                                        Assign as Class Teacher for {{ data_get(collect($classes)->firstWhere('id', $modalClassId), 'name') }}
                                    </span>
                                    <span class="text-amber-700 mt-0.5 block leading-relaxed">
                                        Syncs directly with User Management. Grants authority to take attendance &amp; manage roll numbers for this class.
                                    </span>
                                    @if($currentClassTeacherName)
                                        <div class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium {{ $selectedTeacherId == $currentClassTeacherId ? 'text-emerald-700' : 'text-amber-800' }}">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            @if($selectedTeacherId == $currentClassTeacherId)
                                                <span>Currently active Class Teacher for this class.</span>
                                            @else
                                                <span>Currently assigned to <strong>{{ $currentClassTeacherName }}</strong>. Checking this will reassign the role.</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </label>
                        </div>
                    @endif

                    {{-- Subject Selection: Dropdown with Multiple Subject Selection (Max 3) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Subject(s) <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-400 font-normal ml-1">(Select up to 3)</span>
                            </label>
                            @if(!empty($selectedSubjectIds))
                                <button 
                                    type="button" 
                                    wire:click="clearSubjects"
                                    class="text-xs text-gray-400 hover:text-red-600 transition-colors"
                                >
                                    Clear
                                </button>
                            @endif
                        </div>

                        @if(empty($modalClassId))
                            <div class="p-3 bg-gray-50 border border-dashed border-gray-200 rounded-xl text-xs text-gray-400 text-center">
                                Please select a class above first to load its available subjects.
                            </div>
                        @else
                            <select 
                                wire:model.live="selectedSubjectIds" 
                                multiple 
                                size="4"
                                class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm font-medium text-gray-800"
                            >
                                @foreach($availableSubjects as $subject)
                                    <option value="{{ $subject->id }}" class="py-1">
                                        {{ $subject->name }}{{ !empty($subject->schedule_hint) ? ' ('.$subject->schedule_hint.')' : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="mt-1 flex items-center justify-between text-xs text-gray-400">
                                <span>Hold <kbd class="px-1 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px]">Ctrl</kbd> to select multiple (Max 3)</span>
                                @if(!empty($selectedSubjectIds))
                                    <span class="text-blue-600 font-semibold">
                                        {{ count($selectedSubjectIds) }}/3 selected
                                        @if(count($selectedSubjectIds) === 1)
                                            (Single: full name)
                                        @else
                                            (Multi: abbreviations +)
                                        @endif
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                        <input type="text" wire:model="room" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Room/Lab" />
                    </div>

                    {{-- Apply to All Days / Universal Indicator --}}
                    @if($scheduleType === 'single_schedule')
                        <div class="bg-blue-50/80 p-3 rounded-xl border border-blue-200">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🗓️</span>
                                <span class="text-sm font-semibold text-blue-800">Universal Schedule Active</span>
                            </div>
                            <p class="text-xs text-blue-600 ml-6 mt-0.5">This period assignment will automatically be applied across all active school days ({{ implode(', ', $days) }}).</p>
                        </div>
                    @else
                        <div class="bg-blue-50 p-3 rounded-xl">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="applyToAllDays" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                <span class="text-sm font-medium text-blue-700">Apply to all days</span>
                            </label>
                            <p class="text-xs text-blue-600 ml-6">{{ $applyToAllDays ? 'Will assign to: ' . implode(', ', $days) : 'Assign this schedule to all days (' . implode(', ', array_map(fn($d) => substr($d,0,3), $days)) . ')' }}</p>
                        </div>
                    @endif

                    {{-- Divided Class: Dynamic Slots Repeater --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isDivided" class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500" />
                            <span class="text-sm font-medium text-gray-700">Divided Class (Multiple Teachers)</span>
                        </label>
                        <p class="text-xs text-gray-400 ml-6">For split groups like Bio/Computer/Arts/PE students — each teacher grades their own group</p>
                    </div>

                    @if($isDivided)
                        <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 space-y-4">
                            <p class="text-xs font-semibold text-purple-700 uppercase tracking-wide">Additional Teacher Slots</p>

                            @foreach($dividedSlots as $slotIndex => $slot)
                                <div class="bg-white border border-purple-200 rounded-xl p-3 space-y-2 relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-semibold text-purple-700">Teacher {{ $slotIndex + 2 }}</span>
                                        @if(count($dividedSlots) > 0)
                                            <button wire:click="removeDividedSlot({{ $slotIndex }})" type="button" class="text-red-400 hover:text-red-600 text-xs flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Remove
                                            </button>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Teacher</label>
                                        <select
                                            wire:model.live="dividedSlots.{{ $slotIndex }}.teacher_id"
                                            class="w-full px-3 py-1.5 rounded-lg border border-purple-200 focus:ring-2 focus:purple-400 outline-none bg-white text-sm"
                                        >
                                            <option value="">Select Teacher</option>
                                            @foreach($availableTeachers as $teacher)
                                                @if($teacher->id != $selectedTeacherId)
                                                    <option value="{{ $teacher->id }}" {{ ($slot['teacher_id'] ?? '') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-medium text-gray-600">
                                                Subject(s) <span class="text-gray-400 font-normal">(Max 3)</span>
                                            </label>
                                            @php
                                                $slotSubjectIds = $slot['subject_ids'] ?? (!empty($slot['subject_id']) ? [(int)$slot['subject_id']] : []);
                                            @endphp
                                            @if(!empty($slotSubjectIds))
                                                <span class="text-[10px] font-semibold text-purple-700 bg-purple-100 px-1.5 py-0.5 rounded">
                                                    {{ count($slotSubjectIds) }}/3 selected
                                                </span>
                                            @endif
                                        </div>
                                        <select
                                            wire:model.live="dividedSlots.{{ $slotIndex }}.subject_ids"
                                            multiple
                                            size="3"
                                            class="w-full px-3 py-1.5 rounded-lg border border-purple-200 focus:ring-2 focus:ring-purple-400 outline-none bg-white text-sm font-medium text-gray-800"
                                        >
                                            @foreach($availableSubjects as $subject)
                                                <option value="{{ $subject->id }}">
                                                    {{ $subject->name }}{{ !empty($subject->schedule_hint) ? ' ('.$subject->schedule_hint.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Room (optional)</label>
                                        <input type="text" wire:model="dividedSlots.{{ $slotIndex }}.room" placeholder="Room / Lab" class="w-full px-3 py-1.5 rounded-lg border border-purple-200 focus:ring-2 focus:ring-purple-400 outline-none text-sm" />
                                    </div>
                                </div>
                            @endforeach

                            @if(count($dividedSlots) < 4)
                                <button wire:click="addDividedSlot" type="button" class="w-full py-2 border-2 border-dashed border-purple-300 text-purple-600 rounded-xl text-sm font-medium hover:bg-purple-50 hover:border-purple-400 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add Another Teacher Slot ({{ count($dividedSlots) + 2 }}/{{ 5 }} max)
                                </button>
                            @endif
                        </div>
                    @endif

                    {{-- Period Merge --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="isMerged"
                                class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                                {{ empty($modalClassId) ? 'disabled' : '' }}
                            />
                            <span class="text-sm font-medium text-gray-700">Merge with Other Section(s)</span>
                        </label>
                        <p class="text-xs text-gray-400 ml-6">
                            @if(empty($modalClassId))
                                Select a class above first to merge with other sections
                            @else
                                Teacher will appear in merged sections' timetables simultaneously for this period
                            @endif
                        </p>
                    </div>

                    @if($isMerged && $modalClassId)
                        <div class="bg-teal-50 border border-teal-100 rounded-xl p-4">
                            <p class="text-xs font-semibold text-teal-700 uppercase tracking-wide mb-3">Select Partner Section(s) to Merge</p>
                            @if($this->availableMergeClasses->isEmpty())
                                <p class="text-xs text-teal-700 italic">No other sections or classes available in this session to merge with.</p>
                            @else
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    @foreach($this->availableMergeClasses as $mergeClass)
                                        <label class="flex items-center gap-2.5 cursor-pointer p-2 rounded-lg hover:bg-teal-100 transition-all">
                                            <input
                                                type="checkbox"
                                                value="{{ $mergeClass->id }}"
                                                wire:model.live="mergedClassIds"
                                                class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                                            />
                                            <span class="text-sm font-medium text-teal-900">{{ $mergeClass->name }}</span>
                                            @if(in_array($mergeClass->id, $this->busyClassIds))
                                                <span class="text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded ml-auto">Has period (will be overwritten)</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                                @if(!empty($mergedClassIds))
                                    @php
                                        $partnerNames = \App\Models\Classes::withoutGlobalScope('active_session')->whereIn('id', $mergedClassIds)->pluck('name')->join(', ');
                                    @endphp
                                    <div class="mt-3 text-xs text-teal-700 bg-teal-100 rounded-lg p-2">
                                        ✅ Teacher will appear in timetables of: <strong>{{ $partnerNames }}</strong>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 mt-6">
                    <button wire:click="save" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium">
                        {{ $editingId ? 'Update' : 'Assign' }}
                    </button>
                    @if($editingId)
                        <button wire:click="delete" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 font-medium">
                            Delete
                        </button>
                    @endif
                    <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-medium">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- PRINT TIMETABLES MODAL (Clean, Smooth, Optimized Layout)     --}}
    {{-- =========================================================== --}}
    <div 
        x-show="showPrintModal" 
        x-cloak 
        @keydown.escape.window="showPrintModal = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
        style="display: none;"
    >
        {{-- Centered Flex Wrapper with equal top/bottom breathing margin --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
            {{-- Backdrop --}}
            <div 
                x-show="showPrintModal" 
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" 
                @click="showPrintModal = false"
            ></div>

            {{-- Modal Content Panel --}}
            <div 
                x-show="showPrintModal" 
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-lg border border-slate-200/90 z-10 flex flex-col my-auto"
            >
                {{-- Clean Minimalist Header --}}
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shadow-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">Print Timetables</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Select format to print and auto-download official PDF</p>
                        </div>
                    </div>
                    <button 
                        type="button"
                        @click="showPrintModal = false" 
                        class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                        title="Close (Esc)"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Segmented Tabs --}}
                <div class="px-6 pt-3.5 pb-0 bg-white">
                    <div class="flex p-1 bg-slate-100/80 rounded-xl text-xs font-medium text-slate-600 gap-1 border border-slate-200/60">
                        <button 
                            type="button"
                            @click="activePrintTab = 'master'"
                            :class="activePrintTab === 'master' ? 'bg-white text-indigo-700 shadow-xs font-semibold ring-1 ring-slate-900/5' : 'text-slate-500 hover:text-slate-800'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Master Matrices
                        </button>
                        <button 
                            type="button"
                            @click="activePrintTab = 'class'"
                            :class="activePrintTab === 'class' ? 'bg-white text-indigo-700 shadow-xs font-semibold ring-1 ring-slate-900/5' : 'text-slate-500 hover:text-slate-800'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            By Class
                        </button>
                        <button 
                            type="button"
                            @click="activePrintTab = 'teacher'"
                            :class="activePrintTab === 'teacher' ? 'bg-white text-indigo-700 shadow-xs font-semibold ring-1 ring-slate-900/5' : 'text-slate-500 hover:text-slate-800'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Teachers
                        </button>
                    </div>
                </div>

                {{-- Modal Body with balanced margins and padding --}}
                <div class="px-6 py-4 space-y-3 max-h-[calc(85vh-160px)] overflow-y-auto">
                    {{-- 1. TAB: MASTER MATRICES --}}
                    <div x-show="activePrintTab === 'master'" class="space-y-3">
                        {{-- Class-Wise Master --}}
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/30 hover:bg-white hover:border-indigo-200 hover:shadow-xs transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-slate-900">Class-Wise Master Matrix</h4>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100/70">A4 Landscape</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                        Whole school routine with classes as rows and assembly & break vertical bands.
                                    </p>
                                </div>
                                <a 
                                    href="/admin/schedule/print/master-classwise?autoprint=1" 
                                    target="_blank"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-all shadow-xs shadow-indigo-600/15 flex-shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print Timetable
                                </a>
                            </div>
                        </div>

                        {{-- Teacher-Wise Master --}}
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/30 hover:bg-white hover:border-indigo-200 hover:shadow-xs transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-slate-900">Teacher-Wise Master Matrix</h4>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100/70">A4 Landscape</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                        All faculty schedules with period allocations and total lessons sum.
                                    </p>
                                </div>
                                <a 
                                    href="/admin/schedule/print/master-teacherwise?autoprint=1" 
                                    target="_blank"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-all shadow-xs shadow-indigo-600/15 flex-shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print Timetable
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 2. TAB: CLASS TIMETABLE --}}
                    <div x-show="activePrintTab === 'class'" style="display: none;" class="space-y-3">
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-white">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-slate-900">Select Class</label>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100/70">A4 Landscape</span>
                            </div>
                            <select 
                                x-model="selectedPrintClassId" 
                                class="w-full rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/60 hover:bg-white focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5 px-3 transition-all"
                            >
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->class_teacher_name ? '• CT: '.$c->class_teacher_name : '' }}</option>
                                @endforeach
                            </select>

                            <p class="text-[11px] text-slate-500 mt-2.5 leading-relaxed">
                                Formatted in A4 Landscape with period times, designated subjects, and class teacher details.
                            </p>

                            <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-end">
                                <a 
                                    :href="'/admin/schedule/print/class/' + (selectedPrintClassId || '') + '?autoprint=1'" 
                                    target="_blank"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-all shadow-xs shadow-indigo-600/15"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print Class Timetable
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 3. TAB: TEACHER TIMETABLES (Clean Searchable Multi-Select Combobox) --}}
                    <div 
                        x-show="activePrintTab === 'teacher'" 
                        style="display: none;" 
                        class="space-y-3"
                        x-data="{
                            open: false,
                            search: '',
                            selectedTeacherIds: [{{ ($firstTid = data_get(collect($teachers)->first(), 'id')) !== null ? json_encode($firstTid) : '' }}].filter(Boolean),
                            teachersList: {{ \Illuminate\Support\Js::from(collect($teachers)->map(fn($t) => [
                                'id' => data_get($t, 'id'),
                                'name' => data_get($t, 'name'),
                                'period_count' => count(data_get($teacherGridMap, data_get($t, 'id'), []))
                            ])->values()) }},
                            get filteredTeachers() {
                                if (!this.search || !this.search.trim()) return this.teachersList;
                                const q = this.search.toLowerCase().trim();
                                return this.teachersList.filter(t => (t.name || '').toLowerCase().includes(q));
                            },
                            isSelected(id) {
                                return this.selectedTeacherIds.some(i => String(i) === String(id));
                            },
                            toggleTeacher(id) {
                                const idStr = String(id);
                                const idx = this.selectedTeacherIds.findIndex(i => String(i) === idStr);
                                if (idx > -1) {
                                    this.selectedTeacherIds.splice(idx, 1);
                                } else {
                                    this.selectedTeacherIds.push(id);
                                }
                            },
                            removeTeacher(id) {
                                const idStr = String(id);
                                const idx = this.selectedTeacherIds.findIndex(i => String(i) === idStr);
                                if (idx > -1) {
                                    this.selectedTeacherIds.splice(idx, 1);
                                }
                            },
                            selectAll() {
                                this.selectedTeacherIds = this.teachersList.map(t => t.id);
                            },
                            clearAll() {
                                this.selectedTeacherIds = [];
                            },
                            get selectedSummaryText() {
                                if (this.selectedTeacherIds.length === 0) return '';
                                if (this.selectedTeacherIds.length === this.teachersList.length) return 'All teachers selected (' + this.teachersList.length + ')';
                                return this.selectedTeacherIds.length + ' teachers selected';
                            }
                        }"
                    >
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-white">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-slate-900">Select Teachers</label>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100/70">6 Cards / A4 Landscape</span>
                            </div>

                            {{-- Standard Combobox Field (Click to Open Dropdown) --}}
                            <div class="relative" @click.outside="open = false">
                                <div 
                                    @click="open = !open; if(open) $nextTick(() => $refs.teacherSearchInput.focus())"
                                    class="min-h-[42px] w-full rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/60 hover:bg-white focus-within:bg-white focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 py-1.5 px-3 transition-all cursor-pointer flex items-center justify-between gap-2"
                                >
                                    {{-- Selected Tags / Summary Display --}}
                                    <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0 py-0.5">
                                        <template x-if="selectedTeacherIds.length === 0">
                                            <span class="text-slate-400 font-normal">Choose teachers to print...</span>
                                        </template>

                                        {{-- 1-2 Selected: Clean removable chip tags --}}
                                        <template x-if="selectedTeacherIds.length > 0 && selectedTeacherIds.length <= 2">
                                            <div class="flex flex-wrap gap-1 items-center">
                                                <template x-for="tid in selectedTeacherIds" :key="'chip-' + tid">
                                                    <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[11px] font-medium border border-indigo-100/80">
                                                        <span x-text="teachersList.find(t => String(t.id) === String(tid))?.name || 'Teacher'" class="max-w-[130px] truncate"></span>
                                                        <button 
                                                            type="button" 
                                                            @click.stop="removeTeacher(tid)" 
                                                            class="hover:bg-indigo-200/60 rounded-full p-0.5 text-indigo-500 hover:text-indigo-800 transition-colors"
                                                            title="Remove"
                                                        >
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- > 2 Selected: Clean counter badge --}}
                                        <template x-if="selectedTeacherIds.length > 2">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[11px] font-semibold border border-indigo-100">
                                                <span x-text="selectedSummaryText"></span>
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Right Icons (Clear & Chevron) --}}
                                    <div class="flex items-center gap-1.5 text-slate-400 flex-shrink-0">
                                        <button 
                                            type="button" 
                                            x-show="selectedTeacherIds.length > 0" 
                                            @click.stop="clearAll()" 
                                            class="p-1 hover:text-slate-600 rounded-md hover:bg-slate-200/50 transition-colors"
                                            title="Clear all selections"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <svg 
                                            class="w-4 h-4 transition-transform duration-200" 
                                            :class="open ? 'transform rotate-180 text-indigo-600' : ''" 
                                            fill="none" 
                                            viewBox="0 0 24 24" 
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>

                                {{-- Dropdown Popover with Integrated Search & Checklist --}}
                                <div 
                                    x-show="open" 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute left-0 right-0 z-50 mt-1.5 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden"
                                    style="display: none;"
                                >
                                    {{-- In-Built Search Box --}}
                                    <div class="p-2 border-b border-slate-100 bg-slate-50/70">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                            <input 
                                                x-ref="teacherSearchInput"
                                                type="text" 
                                                x-model="search" 
                                                placeholder="Search teacher name..." 
                                                class="w-full pl-8 pr-7 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 placeholder-slate-400 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                                @keydown.escape="open = false"
                                            />
                                            <button 
                                                type="button" 
                                                x-show="search.length > 0" 
                                                @click="search = ''; $refs.teacherSearchInput.focus()" 
                                                class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600"
                                            >
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- Quick Actions Row (Select All / Deselect) --}}
                                        <div class="flex items-center justify-between mt-2 px-1 text-[11px]">
                                            <span class="text-slate-500 font-medium">
                                                <span x-text="selectedTeacherIds.length"></span> of <span x-text="teachersList.length"></span> selected
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <button 
                                                    type="button" 
                                                    @click="selectAll()" 
                                                    class="text-indigo-600 hover:text-indigo-800 font-semibold"
                                                >
                                                    Select All
                                                </button>
                                                <span class="text-slate-300">|</span>
                                                <button 
                                                    type="button" 
                                                    @click="clearAll()" 
                                                    class="text-slate-400 hover:text-rose-600 font-medium"
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Scrollable Checklist without label collision bugs --}}
                                    <div class="max-h-52 overflow-y-auto p-1 divide-y divide-slate-50">
                                        <template x-for="t in filteredTeachers" :key="'teacher-item-' + t.id">
                                            <div 
                                                role="button"
                                                tabindex="0"
                                                @click="toggleTeacher(t.id)"
                                                @keydown.space.prevent="toggleTeacher(t.id)"
                                                @keydown.enter.prevent="toggleTeacher(t.id)"
                                                class="flex items-center justify-between px-2.5 py-2 rounded-lg hover:bg-indigo-50/70 cursor-pointer transition-colors group select-none"
                                                :class="isSelected(t.id) ? 'bg-indigo-50/40 text-indigo-950 font-medium' : 'text-slate-700'"
                                            >
                                                <div class="flex items-center gap-2.5 min-w-0 pointer-events-none">
                                                    <span 
                                                        class="w-4 h-4 rounded border flex items-center justify-center transition-all flex-shrink-0"
                                                        :class="isSelected(t.id) ? 'bg-indigo-600 border-indigo-600 text-white shadow-2xs' : 'border-slate-300 bg-white group-hover:border-indigo-400'"
                                                    >
                                                        <svg x-show="isSelected(t.id)" class="w-2.5 h-2.5 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </span>
                                                    <span 
                                                        :class="isSelected(t.id) ? 'font-semibold text-indigo-950' : 'text-slate-700'"
                                                        class="text-xs truncate" 
                                                        x-text="t.name"
                                                    ></span>
                                                </div>
                                                <span 
                                                    class="text-[10px] px-2 py-0.5 rounded-md transition-colors pointer-events-none font-medium"
                                                    :class="isSelected(t.id) ? 'bg-indigo-100/70 text-indigo-800' : 'text-slate-400 group-hover:text-slate-600 bg-slate-100/60'"
                                                    x-text="t.period_count + ' period' + (t.period_count === 1 ? '' : 's')"
                                                ></span>
                                            </div>
                                        </template>

                                        {{-- Empty Search State --}}
                                        <div x-show="filteredTeachers.length === 0" class="py-6 text-center text-xs text-slate-400">
                                            No teachers found matching "<span x-text="search"></span>"
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="text-[11px] text-slate-500 mt-2.5 leading-relaxed">
                                Formatted in A4 Landscape 3×2 grid (6 cards per page) with period timings, designated classes, and subject allocations.
                            </p>

                            {{-- Action Buttons & Metrics Bar --}}
                            <div class="mt-3.5 pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                                <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                    <template x-if="selectedTeacherIds.length === 0">
                                        <span class="text-amber-600 font-medium flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Select at least 1 teacher to print
                                        </span>
                                    </template>
                                    <template x-if="selectedTeacherIds.length > 0">
                                        <span>
                                            <strong class="text-slate-800" x-text="selectedTeacherIds.length"></strong> teacher(s) selected
                                            <span class="text-slate-400">•</span>
                                            <span class="text-indigo-600 font-semibold" x-text="'~' + Math.ceil(selectedTeacherIds.length / 6) + ' A4 page' + (Math.ceil(selectedTeacherIds.length / 6) !== 1 ? 's' : '')"></span>
                                        </span>
                                    </template>
                                </div>

                                <div class="flex items-center gap-2 justify-end">
                                    {{-- Single Slip option if exactly 1 teacher selected --}}
                                    <template x-if="selectedTeacherIds.length === 1">
                                        <a 
                                            :href="'/admin/schedule/print/teacher/' + selectedTeacherIds[0] + '?autoprint=1'" 
                                            target="_blank"
                                            class="inline-flex items-center justify-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200 transition-all shadow-2xs"
                                            title="Print single diary card slip"
                                        >
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            Single Slip
                                        </a>
                                    </template>

                                    {{-- Primary 6-Up Dossier Print Action --}}
                                    <a 
                                        :href="'/admin/schedule/print/teachers-bulk?teachers=' + selectedTeacherIds.join(',') + '&autoprint=1'" 
                                        target="_blank"
                                        :class="selectedTeacherIds.length > 0 ? 'bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white shadow-xs shadow-indigo-600/15' : 'bg-slate-200 text-slate-400 pointer-events-none cursor-not-allowed'"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold transition-all"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span x-text="selectedTeacherIds.length === teachersList.length ? 'Print All (' + teachersList.length + ' in 6-Up)' : ('Print Selected (' + selectedTeacherIds.length + ' in 6-Up)')"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Clean Minimalist Footer --}}
                <div class="px-6 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Auto-downloads PDF & opens print dialog
                    </span>
                    <button 
                        type="button" 
                        @click="showPrintModal = false"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
