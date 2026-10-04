<div class="space-y-6" x-data="{ showPrintModal: false, activePrintTab: 'master', selectedPrintClassId: '{{ $classes->first()?->id ?? '' }}', selectedPrintTeacherId: '{{ $teachers->first()?->id ?? '' }}' }">
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
                wire:confirm="Sync all timetable teacher and subject assignments directly into Gradebook and User Management?"
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
                    wire:confirm="Copy {{ $selectedDay }}'s schedule to all other weekdays? This will replace existing schedules."
                    class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    Copy to All
                </button>
                <button
                    wire:click="clearDay"
                    wire:confirm="Clear all schedule entries for {{ $selectedDay }}?"
                    class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Day
                </button>
            @else
                <button
                    wire:click="clearDay"
                    wire:confirm="Clear all entries in the universal schedule across all school days?"
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
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase w-32 sticky left-0 bg-gray-50">Class</th>
                        @foreach($periods as $period)
                            <th class="px-2 py-3 text-center text-xs font-medium {{ $period->is_break ? 'bg-yellow-50 text-yellow-700' : ($period->is_assembly ? 'bg-purple-50 text-purple-700' : 'text-gray-500') }} min-w-[140px]">
                                <div class="font-bold">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($classes as $class)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-sm font-bold text-gray-800 sticky left-0 bg-white group/classheader">
                                <div class="flex items-center justify-between gap-1.5">
                                    <div>{{ $class->name }}</div>
                                    <a 
                                        href="/admin/schedule/print/class/{{ $class->id }}?autoprint=1" 
                                        target="_blank" 
                                        class="opacity-0 group-hover/classheader:opacity-100 transition-opacity p-1 text-gray-400 hover:text-indigo-600 rounded hover:bg-indigo-50" 
                                        title="Print Class {{ $class->name }} Timetable"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </a>
                                </div>
                                @if(!empty($class->class_teacher_name))
                                    <div class="text-[10px] font-medium text-amber-600 truncate flex items-center gap-1 mt-0.5" title="Class Teacher: {{ $class->class_teacher_name }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                                        <span class="truncate">CT: {{ $class->class_teacher_name }}</span>
                                    </div>
                                @endif
                            </td>
                            @foreach($periods as $period)
                                @if($period->is_break)
                                    <td class="px-2 py-3 bg-yellow-50/50 text-center">
                                        <span class="text-yellow-600 text-xs">Break</span>
                                    </td>
                                @elseif($period->is_assembly)
                                    <td class="px-2 py-3 bg-purple-50/50 text-center">
                                        <span class="text-purple-600 text-xs">Assembly</span>
                                    </td>
                                @else
                                    @php $schedules = $this->getSchedule($class->id, $period->period_no); @endphp
                                    <td
                                        wire:click="openModal({{ $class->id }}, {{ $period->period_no }})"
                                        class="px-2 py-2 cursor-pointer border-l border-gray-100 {{ $schedules->isNotEmpty() ? 'hover:bg-blue-50/80' : 'hover:bg-emerald-50/70' }} transition-all group"
                                        title="{{ $schedules->isNotEmpty() ? 'Edit assignment for ' . $class->name . ' in ' . $period->label : 'Assign period for ' . $class->name . ' in ' . $period->label }}"
                                    >
                                        @if($schedules->isNotEmpty())
                                            <div class="flex flex-col gap-1">
                                        @foreach($schedules as $schedule)
                                            @php
                                                $teacher = collect($teachers)->firstWhere('id', $schedule->teacher_id);
                                                $subject = \App\Models\Subject::find($schedule->subject_id);
                                                $partnerLabel = $mergedPartnerClassesMap[$schedule->id] ?? null;
                                            @endphp
                                            <div class="text-xs space-y-0.5 {{ $loop->index > 0 ? 'border-t border-gray-200 pt-1' : '' }}">
                                                <div class="font-bold text-blue-700 truncate">{{ $subject->name ?? '-' }}</div>
                                                <div class="text-gray-500 truncate">{{ $teacher->name ?? '-' }}</div>
                                                @if($schedule->is_merged && $partnerLabel)
                                                    <span class="text-[10px] text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded-full border border-teal-200" title="Merged with {{ $partnerLabel }}">🔗 +{{ $partnerLabel }}</span>
                                                @endif
                                                @if($schedule->is_divided && $loop->last)
                                                    <span class="text-[10px] text-purple-600 bg-purple-50 px-1 rounded">Divided</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                        @else
                                            <div class="flex items-center justify-center h-full">
                                                <span class="text-[11px] text-emerald-600 bg-emerald-50 group-hover:bg-emerald-100 group-hover:text-emerald-700 px-2.5 py-1 rounded-lg font-semibold transition-all flex items-center gap-1 border border-emerald-200/60 shadow-xs">
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
        <div class="px-4 py-2.5 bg-indigo-50/60 border-b border-indigo-100 flex items-center gap-4 text-xs text-indigo-700">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><strong>Teacher View</strong> — rows are teachers, columns are periods. Click any cell to assign or edit a class period.</span>
            <span class="ml-auto flex items-center gap-3">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-indigo-100 border border-indigo-300"></span> Assigned</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span> Free (Click to assign)</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-purple-100 border border-purple-300"></span> Divided</span>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase w-36 sticky left-0 bg-gray-50">Teacher</th>
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
                        <tr class="hover:bg-indigo-50/20 transition-colors">
                            {{-- Teacher Name Cell --}}
                            <td class="px-4 py-3 sticky left-0 bg-white z-10 group/teacherheader">
                                <div class="flex items-center justify-between gap-1.5">
                                    <div class="text-sm font-bold text-gray-800 truncate max-w-[110px]" title="{{ $teacher->name }}">
                                        {{ $teacher->name }}
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
                                @php
                                    $teacherPeriodCount = isset($teacherGridMap[$teacher->id])
                                        ? count($teacherGridMap[$teacher->id])
                                        : 0;
                                @endphp
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ $teacherPeriodCount }} period{{ $teacherPeriodCount !== 1 ? 's' : '' }} assigned
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
                                            <div class="flex flex-col gap-1">
                                                @foreach($cellRows as $idx => $row)
                                                    @php
                                                        $subject = \App\Models\Subject::find($row->subject_id);
                                                        $classObj = $classes->firstWhere('id', $row->class_id);
                                                    @endphp
                                                    <div class="text-xs {{ $idx > 0 ? 'border-t border-indigo-100 pt-1' : '' }}">
                                                        <div class="font-bold text-indigo-700 truncate">
                                                            {{ $classObj->name ?? ('Class #'.$row->class_id) }}
                                                        </div>
                                                        <div class="text-gray-500 truncate">{{ $subject->name ?? '—' }}</div>
                                                        @if($row->is_divided)
                                                            <span class="text-[10px] text-purple-600 bg-purple-50 px-1 rounded">Divided</span>
                                                        @endif
                                                        @if($row->is_merged && ($mergedPartnerClassesMap[$row->id] ?? null))
                                                            <span class="text-[10px] text-teal-700 bg-teal-50 px-1 rounded">🔗 +{{ $mergedPartnerClassesMap[$row->id] }}</span>
                                                        @endif
                                                        @if($row->room)
                                                            <div class="text-[10px] text-gray-400">{{ $row->room }}</div>
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

    {{-- Assignment Modal --}}
    @if($showModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit Period Assignment' : 'Assign Period' }}</h2>
                        <p class="text-sm text-gray-500">
                            @if($modalClassId && $classes->firstWhere('id', $modalClassId))
                                <span class="font-semibold text-gray-700">{{ $classes->firstWhere('id', $modalClassId)->name }}</span> •
                            @elseif($selectedTeacherId && $teachers->firstWhere('id', $selectedTeacherId))
                                <span class="font-semibold text-indigo-700">{{ $teachers->firstWhere('id', $selectedTeacherId)->name }}</span> •
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
                    @endif

                    {{-- Main Assignment --}}
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

                    {{-- Optional Class Teacher Assignment --}}
                    @if($selectedTeacherId && $modalClassId)
                        <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-3 transition-all">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" wire:model.live="setAsClassTeacher" class="mt-0.5 w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" />
                                <div class="flex-1 text-xs">
                                    <span class="font-semibold text-amber-900 block text-sm">
                                        Assign as Class Teacher for {{ $classes->firstWhere('id', $modalClassId)?->name }}
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="selectedSubjectId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white font-medium text-gray-800" {{ empty($modalClassId) ? 'disabled' : '' }}>
                            <option value="">{{ empty($modalClassId) ? 'Select a Class first' : 'Select Subject' }}</option>
                            @foreach($availableSubjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->schedule_hint ?? '' }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ empty($modalClassId) ? 'Class selection is required to display subjects' : 'Multiple periods of the same subject on the same day are supported' }}
                        </p>
                    </div>{{-- close subject div --}}
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
                                            class="w-full px-3 py-1.5 rounded-lg border border-purple-200 focus:ring-2 focus:ring-purple-400 outline-none bg-white text-sm"
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
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                                        <select
                                            wire:model.live="dividedSlots.{{ $slotIndex }}.subject_id"
                                            class="w-full px-3 py-1.5 rounded-lg border border-purple-200 focus:ring-2 focus:ring-purple-400 outline-none bg-white text-sm"
                                        >
                                            <option value="">Select Subject</option>
                                            @foreach($availableSubjects as $subject)
                                                <option value="{{ $subject->id }}" {{ ($slot['subject_id'] ?? '') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}{{ $subject->schedule_hint ?? '' }}</option>
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
                        <button wire:click="delete" wire:confirm="Remove this assignment?" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 font-medium">
                            Delete
                        </button>
                    @endif
                    <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-medium">
                        Cancel
                    </button>
                </div>
            </div>
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

                    {{-- 3. TAB: TEACHER TIMETABLES --}}
                    <div x-show="activePrintTab === 'teacher'" style="display: none;" class="space-y-3">
                        {{-- Individual Teacher Slip --}}
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-white">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-slate-900">Individual Teacher Slip</label>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100/70">Diary Card</span>
                            </div>
                            <select 
                                x-model="selectedPrintTeacherId" 
                                class="w-full rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/60 hover:bg-white focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 py-2.5 px-3 transition-all"
                            >
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>

                            <p class="text-[11px] text-slate-500 mt-2.5 leading-relaxed">
                                2-column compact diary slip showing periods, assigned classes, and room allocations.
                            </p>

                            <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-end">
                                <a 
                                    :href="'/admin/schedule/print/teacher/' + (selectedPrintTeacherId || '') + '?autoprint=1'" 
                                    target="_blank"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-all shadow-xs shadow-indigo-600/15"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print Teacher Slip
                                </a>
                            </div>
                        </div>

                        {{-- Bulk All Teachers Dossier --}}
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/30 hover:bg-white hover:border-indigo-200 hover:shadow-xs transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-slate-900">Bulk All Teachers Dossier</h4>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100/70">6 Cards / A4</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                        3×2 grid on A4 Landscape with cutting lines for all faculty members.
                                    </p>
                                </div>
                                <a 
                                    href="/admin/schedule/print/teachers-bulk?autoprint=1" 
                                    target="_blank"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-all shadow-xs shadow-indigo-600/15 flex-shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print All (6-Up)
                                </a>
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
