<div class="space-y-6" x-data="{ 
    showPrintModal: false, 
    activePrintTab: 'master', 
    activeCohort: 'all',
    selectedPrintClassId: '{{ data_get(collect($classes)->first(), 'id', '') }}', 
    selectedPrintTeacherId: '{{ data_get(collect($teachers)->first(), 'id', '') }}',
    selectedPrintDay: 'all'
}">
    @php
        // Helper to categorize subjects into distinct department color accents
        $getSubjectBadge = function($subjectName) {
            $name = strtolower($subjectName ?? '');
            if (str_contains($name, 'math')) {
                return [
                    'text' => 'text-emerald-700',
                    'bg' => 'bg-emerald-50',
                    'border' => 'border-emerald-200/90',
                    'bar' => 'bg-emerald-500'
                ];
            }
            if (str_contains($name, 'phy') || str_contains($name, 'chem') || str_contains($name, 'bio') || str_contains($name, 'sci') || str_contains($name, 'comp') || str_contains($name, 'it')) {
                return [
                    'text' => 'text-sky-700',
                    'bg' => 'bg-sky-50',
                    'border' => 'border-sky-200/90',
                    'bar' => 'bg-sky-500'
                ];
            }
            if (str_contains($name, 'eng') || str_contains($name, 'urd') || str_contains($name, 'isl') || str_contains($name, 'arab') || str_contains($name, 'quran')) {
                return [
                    'text' => 'text-indigo-700',
                    'bg' => 'bg-indigo-50',
                    'border' => 'border-indigo-200/90',
                    'bar' => 'bg-indigo-500'
                ];
            }
            if (str_contains($name, 'pak') || str_contains($name, 'hist') || str_contains($name, 'geo') || str_contains($name, 'civic') || str_contains($name, 'soc') || str_contains($name, 'art') || str_contains($name, 'agri')) {
                return [
                    'text' => 'text-amber-800',
                    'bg' => 'bg-amber-50',
                    'border' => 'border-amber-200/90',
                    'bar' => 'bg-amber-500'
                ];
            }
            return [
                'text' => 'text-slate-700',
                'bg' => 'bg-slate-50',
                'border' => 'border-slate-200/90',
                'bar' => 'bg-slate-400'
            ];
        };
    @endphp

    {{-- Top Header --}}
    <div class="flex justify-between items-center">
        <div class="flex items-start gap-4">
            <x-schedule-menu />
            <div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Schedule Management</h1>
                <p class="text-gray-500 text-sm">Assign teachers and subjects to classes with conflict-free validation</p>
            </div>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-200 p-4 rounded-xl text-green-800 text-sm flex items-center gap-2 shadow-xs">
            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-50 border border-red-200 p-4 rounded-xl text-red-800 text-sm flex items-center gap-2 shadow-xs">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Day Tabs + View Toggle + Action Bar --}}
    <div class="flex flex-wrap gap-3 justify-between items-center border-b border-gray-200 pb-3">
        {{-- Left: Day Tabs / Single Schedule Status --}}
        @if($scheduleType === 'single_schedule')
            <div class="flex items-center gap-2.5 py-1">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold text-sm bg-blue-50 text-blue-800 border border-blue-200 shadow-xs">
                    <span class="text-base">🗓️</span>
                    <span>Universal Routine Mode</span>
                </span>
                <span class="text-xs text-gray-500 font-medium">
                    (Unified timetable synced across all {{ count($days) }} active school days)
                </span>
            </div>
        @else
            <div class="flex gap-1.5 items-center flex-wrap">
                @foreach($days as $day)
                    <button
                        wire:click="$set('selectedDay', '{{ $day }}')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-2xs {{ $selectedDay === $day ? 'bg-blue-600 text-white shadow-blue-500/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80 hover:text-gray-800' }}"
                    >
                        {{ $day }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Right: View Toggle + Action Buttons --}}
        <div class="flex gap-2 items-center flex-wrap">
            {{-- View Mode Toggle --}}
            <div class="flex items-center bg-gray-100 rounded-xl p-1 gap-0.5 border border-gray-200/60" role="group" aria-label="Grid view mode">
                <button
                    wire:click="$set('viewMode', 'class')"
                    id="view-toggle-class"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150
                        {{ $viewMode === 'class'
                            ? 'bg-white text-blue-700 shadow-xs ring-1 ring-blue-200'
                            : 'text-gray-500 hover:text-gray-800' }}"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M14 3v18"/>
                    </svg>
                    By Class
                </button>
                <button
                    wire:click="$set('viewMode', 'teacher')"
                    id="view-toggle-teacher"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150
                        {{ $viewMode === 'teacher'
                            ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-indigo-200'
                            : 'text-gray-500 hover:text-gray-800' }}"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    By Teacher
                </button>
            </div>

            <div class="w-px h-6 bg-gray-300 mx-1"></div>

            <button
                wire:click="syncAllocations"
                class="px-3.5 py-2 text-xs bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors flex items-center gap-1.5 shadow-xs font-bold"
                title="Synchronize all timetable entries into Teacher Gradebook and User Management"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync to Gradebook
            </button>

            <button
                type="button"
                @click="showPrintModal = true"
                class="px-3.5 py-2 text-xs bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors flex items-center gap-1.5 shadow-xs font-bold"
                title="Print Master, Class, and Teacher Timetables"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Timetables
            </button>

            @if($scheduleType !== 'single_schedule')
                <button
                    wire:click="copyToAllDays"
                    class="px-3 py-2 text-xs bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors flex items-center gap-1 font-bold shadow-xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    Copy to All
                </button>
                <button
                    wire:click="clearDay"
                    class="px-3 py-2 text-xs bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors flex items-center gap-1 font-bold shadow-xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Day
                </button>
            @else
                <button
                    wire:click="clearDay"
                    class="px-3 py-2 text-xs bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors flex items-center gap-1 font-bold shadow-xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Schedule
                </button>
            @endif
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- CLASS VIEW GRID                                              --}}
    {{-- =========================================================== --}}
    @if($viewMode === 'class')
    <div class="glass-card rounded-2xl overflow-hidden border border-gray-200/90 shadow-sm bg-white">
        {{-- Class View Legend & Cohort Filters --}}
        <div class="px-4 py-2.5 bg-blue-50/60 border-b border-blue-100 flex flex-wrap items-center justify-between gap-3 text-xs text-blue-800">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span><strong>Class Matrix</strong> — Click any assigned cell to edit or an empty cell to allocate a period.</span>
            </div>
            
            {{-- Cohort Selector Pills --}}
            <div class="flex items-center gap-1 bg-white/80 p-0.5 rounded-lg border border-blue-200/70">
                <button type="button" @click="activeCohort = 'all'" :class="activeCohort === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-blue-700 hover:bg-blue-50'" class="px-2 py-0.5 rounded text-[11px] font-bold transition-all">All</button>
                <button type="button" @click="activeCohort = 'middle'" :class="activeCohort === 'middle' ? 'bg-blue-600 text-white shadow-xs' : 'text-blue-700 hover:bg-blue-50'" class="px-2 py-0.5 rounded text-[11px] font-bold transition-all">Middle (6-8)</button>
                <button type="button" @click="activeCohort = 'secondary'" :class="activeCohort === 'secondary' ? 'bg-blue-600 text-white shadow-xs' : 'text-blue-700 hover:bg-blue-50'" class="px-2 py-0.5 rounded text-[11px] font-bold transition-all">Secondary (9-10)</button>
                <button type="button" @click="activeCohort = 'college'" :class="activeCohort === 'college' ? 'bg-blue-600 text-white shadow-xs' : 'text-blue-700 hover:bg-blue-50'" class="px-2 py-0.5 rounded text-[11px] font-bold transition-all">College (11-12)</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-3.5 py-2.5 text-left text-xs font-bold text-gray-700 uppercase w-44 sticky left-0 bg-gray-50 z-20 border-r border-gray-200">
                            Class
                        </th>
                        @foreach($periods as $period)
                            <th class="px-2 py-2.5 text-center text-xs font-semibold min-w-[120px]
                                {{ $period->is_break ? 'bg-yellow-50 text-yellow-800' : ($period->is_assembly ? 'bg-purple-50 text-purple-800' : 'text-gray-700') }}">
                                <div class="font-bold">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 font-normal">
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

                            // Determine cohort group for live filtering
                            $rawNum = (int) (data_get($class, 'numeric_value') ?: (preg_match('/\d+/', $className, $m) ? $m[0] : 0));
                            $cohortGroup = ($rawNum >= 6 && $rawNum <= 8) 
                                ? 'middle' 
                                : (($rawNum == 9 || $rawNum == 10) ? 'secondary' : (($rawNum >= 11 && $rawNum <= 12) ? 'college' : 'other'));
                        @endphp
                        <tr 
                            x-show="activeCohort === 'all' || activeCohort === '{{ $cohortGroup }}'" 
                            class="hover:bg-blue-50/20 transition-colors {{ $loop->even ? 'bg-slate-50/30' : '' }}"
                        >
                            {{-- Class Column with Homeroom CT Badge & Quick Print Link --}}
                            <td class="px-3.5 py-2.5 sticky left-0 bg-white z-10 group/classheader border-r border-gray-200 {{ $hasCt ? 'bg-amber-50/20' : '' }}">
                                <div class="flex items-center justify-between gap-1.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $hasCt ? 'bg-amber-500 ring-2 ring-amber-100 shadow-2xs' : 'bg-gray-300' }}" title="{{ $hasCt ? 'Class Teacher: ' . $ctName : 'No Class Teacher Assigned' }}"></span>
                                        <div class="text-sm font-bold text-gray-900 truncate max-w-[120px]" title="{{ $className }}">
                                            {{ $className }}
                                        </div>
                                    </div>
                                    <a 
                                        href="/admin/schedule/print/class/{{ $classId }}?autoprint=1" 
                                        target="_blank" 
                                        class="opacity-0 group-hover/classheader:opacity-100 transition-opacity p-1 text-gray-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50" 
                                        title="Print Class {{ $className }} Timetable"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </a>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-1 text-[10px]">
                                    @if($hasCt)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-semibold text-amber-800 bg-amber-50 border border-amber-200 truncate max-w-[130px]" title="Class Teacher: {{ $ctName }}">
                                            <span class="truncate">CT: {{ $ctName }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-medium text-gray-400 bg-gray-50 border border-gray-100 truncate" title="No Class Teacher Assigned">
                                            <span>No CT Assigned</span>
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Period Columns --}}
                            @foreach($periods as $period)
                                @if($period->is_break)
                                    <td class="px-1.5 py-2 bg-yellow-50/50 text-center border-l border-gray-100">
                                        <span class="text-yellow-700 font-bold text-[10px] tracking-wider uppercase">Break</span>
                                    </td>
                                @elseif($period->is_assembly)
                                    <td class="px-1.5 py-2 bg-purple-50/50 text-center border-l border-gray-100">
                                        <span class="text-purple-700 font-bold text-[10px] tracking-wider uppercase">Assembly</span>
                                    </td>
                                @else
                                    @php $schedules = $this->getSchedule($classId, $period->period_no); @endphp
                                    <td
                                        wire:click="openModal({{ $classId }}, {{ $period->period_no }})"
                                        class="px-2 py-1.5 cursor-pointer border-l border-gray-100 {{ $schedules->isNotEmpty() ? 'hover:bg-blue-50/80' : 'hover:bg-emerald-50/70' }} transition-all group align-middle"
                                        title="{{ $schedules->isNotEmpty() ? 'Edit period for ' . $className : 'Assign period for ' . $className }}"
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
                                                        $badge = $getSubjectBadge($groupSubjects->first()?->name ?? '');
                                                    @endphp
                                                    <div class="text-xs p-1.5 rounded-md border {{ $badge['bg'] }} {{ $badge['border'] }} space-y-0.5 relative overflow-hidden">
                                                        <div class="flex items-center justify-between gap-1">
                                                            <div class="font-bold {{ $badge['text'] }} leading-tight flex items-center flex-wrap gap-1">
                                                                @if($groupSubjects->count() > 1)
                                                                    @foreach($groupSubjects as $sIdx => $sub)
                                                                        @if($sIdx > 0)
                                                                            <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-blue-100 text-blue-700 text-[9px] font-black leading-none shadow-2xs" title="Multi-subject">+</span>
                                                                        @endif
                                                                        <span class="px-1 py-0.2 rounded bg-white/90 text-blue-900 text-[10.5px] font-bold border border-blue-200/60" title="{{ $sub->name }}">
                                                                            {{ $sub->abbreviation ?: substr($sub->name, 0, 4) }}
                                                                        </span>
                                                                    @endforeach
                                                                @elseif($groupSubjects->isNotEmpty())
                                                                    <span class="truncate" title="{{ $groupSubjects->first()?->name }}">{{ $groupSubjects->first()?->name }}</span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            </div>
                                                            @if(!empty($firstRow->room))
                                                                <span class="text-[9px] font-medium text-slate-500 bg-white/80 px-1 rounded border border-slate-200 flex-shrink-0">
                                                                    {{ $firstRow->room }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="text-gray-600 text-[11px] truncate leading-tight font-medium">
                                                            {{ $teacher->name ?? '-' }}
                                                        </div>
                                                        @if($firstRow->is_merged && $partnerLabel)
                                                            <span class="text-[9px] text-teal-800 bg-teal-100/80 px-1 py-0.2 rounded border border-teal-200 inline-block font-semibold" title="Merged with {{ $partnerLabel }}">
                                                                🔗 +{{ $partnerLabel }}
                                                            </span>
                                                        @endif
                                                        @if($firstRow->is_divided && $loop->last)
                                                            <span class="text-[9px] text-purple-800 bg-purple-100/80 px-1 py-0.2 rounded border border-purple-200 inline-block font-semibold">
                                                                Divided
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center h-full py-1">
                                                <span class="text-[10px] text-emerald-700 bg-emerald-50 group-hover:bg-emerald-100 px-2 py-0.5 rounded-md font-semibold transition-all flex items-center gap-1 border border-emerald-200 shadow-2xs">
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
    <div class="glass-card rounded-2xl overflow-hidden border border-gray-200/90 shadow-sm bg-white">
        {{-- Teacher View Legend --}}
        <div class="px-4 py-2.5 bg-indigo-50/60 border-b border-indigo-100 flex flex-wrap items-center justify-between gap-3 text-xs text-indigo-700">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><strong>Teacher Matrix</strong> — Click any assigned cell to edit or an empty cell to allocate a lesson.</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200 text-amber-900 font-semibold shadow-2xs text-[11px]">
                    <span class="w-2 h-2 rounded-full bg-amber-500 ring-2 ring-amber-100 flex-shrink-0"></span>
                    <span>Class Teacher</span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200 text-blue-900 font-semibold shadow-2xs text-[11px]">
                    <span class="w-2 h-2 rounded-full bg-blue-500 ring-2 ring-blue-100 flex-shrink-0"></span>
                    <span>Subject Teacher</span>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-3.5 py-2.5 text-left text-xs font-bold text-gray-700 uppercase w-48 sticky left-0 bg-gray-50 z-20 border-r border-gray-200">
                            Teacher
                        </th>
                        @foreach($periods as $period)
                            <th class="px-2 py-2.5 text-center text-xs font-semibold min-w-[125px]
                                {{ $period->is_break ? 'bg-yellow-50 text-yellow-800' : ($period->is_assembly ? 'bg-purple-50 text-purple-800' : 'text-gray-700') }}">
                                <div class="font-bold">{{ $period->label }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 font-normal">
                                    {{ \Carbon\Carbon::parse($period->start_time)->format('h:i') }}–{{ \Carbon\Carbon::parse($period->end_time)->format('h:i') }}
                                </div>
                            </th>
                        @endforeach
                        <th class="px-3 py-2.5 text-center text-xs font-bold text-indigo-900 uppercase bg-indigo-50/80 border-l border-indigo-100 min-w-[95px] sticky right-0">
                            <div>Total</div>
                            <div class="text-[10px] text-indigo-500 font-medium lowercase">Lessons</div>
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
                        <tr class="hover:bg-indigo-50/20 transition-colors {{ $loop->even ? 'bg-slate-50/30' : '' }}">
                            {{-- Teacher Header Cell --}}
                            <td class="px-3.5 py-2.5 sticky left-0 bg-white z-10 group/teacherheader border-r border-gray-200 {{ $isClassTeacher ? 'bg-amber-50/20' : '' }}">
                                <div class="flex items-center justify-between gap-1.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $isClassTeacher ? 'bg-amber-500 ring-2 ring-amber-100 shadow-2xs' : 'bg-blue-400' }}" title="{{ $isClassTeacher ? 'Class Teacher (' . $ctClassName . ')' : 'Subject Teacher' }}"></span>
                                        <div class="text-sm font-bold text-gray-900 truncate max-w-[130px]" title="{{ $teacher->name }}">
                                            {{ $teacher->name }}
                                        </div>
                                    </div>
                                    <a 
                                        href="/admin/schedule/print/teacher/{{ $teacher->id }}?autoprint=1" 
                                        target="_blank" 
                                        class="opacity-0 group-hover/teacherheader:opacity-100 transition-opacity p-1 text-gray-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50" 
                                        title="Print Slip for {{ $teacher->name }}"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </a>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-1 text-[10px]">
                                    @if($isClassTeacher)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-semibold text-amber-800 bg-amber-50 border border-amber-200 truncate max-w-[110px]" title="Class Teacher of {{ $ctClassName }}">
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

                            {{-- Periods --}}
                            @foreach($periods as $period)
                                @if($period->is_break)
                                    <td class="px-2 py-3 bg-yellow-50/50 text-center">
                                        <span class="text-yellow-600 font-medium text-xs">Break</span>
                                    </td>
                                @elseif($period->is_assembly)
                                    <td class="px-2 py-3 bg-purple-50/50 text-center">
                                        <span class="text-purple-600 font-medium text-xs">Assembly</span>
                                    </td>
                                @else
                                    @php
                                        $cellRows = $teacherGridMap[$teacher->id][$period->period_no] ?? [];
                                    @endphp

                                    @if(count($cellRows) > 0)
                                        <td
                                            wire:click="openModal({{ $cellRows[0]->class_id }}, {{ $period->period_no }}, {{ $teacher->id }})"
                                            class="px-2 py-1.5 cursor-pointer border-l border-gray-100 hover:bg-indigo-50/80 transition-all align-middle"
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
                                                        $badge = $getSubjectBadge($cSubjects->first()?->name ?? '');
                                                    @endphp
                                                    <div class="text-xs p-1.5 rounded-md border {{ $badge['bg'] }} {{ $badge['border'] }} space-y-0.5">
                                                        <div class="flex items-center justify-between gap-1">
                                                            <div class="font-bold {{ $isTeachingHomeClass ? 'text-amber-900' : 'text-indigo-900' }} truncate">
                                                                {{ $classObj->name ?? ('Class #'.$cId) }}
                                                            </div>
                                                            @if($isTeachingHomeClass)
                                                                <span class="text-[9px] font-bold text-amber-800 bg-amber-100 border border-amber-200 px-1 rounded-sm" title="Teaching homeroom class">CT</span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center flex-wrap gap-1 mt-0.5 leading-tight">
                                                            @if($cSubjects->count() > 1)
                                                                @foreach($cSubjects as $sIdx => $sub)
                                                                    @if($sIdx > 0)
                                                                        <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-blue-100 text-blue-700 text-[9px] font-black leading-none shadow-2xs" title="Multi-subject">+</span>
                                                                    @endif
                                                                    <span class="px-1 py-0.2 rounded bg-white/90 text-blue-900 text-[10.5px] font-bold border border-blue-200/60" title="{{ $sub->name }}">
                                                                        {{ $sub->abbreviation ?: substr($sub->name, 0, 4) }}
                                                                    </span>
                                                                @endforeach
                                                            @elseif($cSubjects->isNotEmpty())
                                                                <span class="truncate text-gray-700 font-medium" title="{{ $cSubjects->first()?->name }}">{{ $cSubjects->first()?->name }}</span>
                                                            @else
                                                                <span class="text-gray-400">—</span>
                                                            @endif
                                                        </div>
                                                        @if($firstCRow->is_divided)
                                                            <span class="text-[9px] text-purple-700 bg-purple-100/70 px-1 rounded">Divided</span>
                                                        @endif
                                                        @if($firstCRow->is_merged && ($mergedPartnerClassesMap[$firstCRow->id] ?? null))
                                                            <span class="text-[9px] text-teal-700 bg-teal-100/70 px-1 rounded">🔗 +{{ $mergedPartnerClassesMap[$firstCRow->id] }}</span>
                                                        @endif
                                                        @if($firstCRow->room)
                                                            <div class="text-[9px] text-gray-500">{{ $firstCRow->room }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    @else
                                        <td
                                            wire:click="openModal(null, {{ $period->period_no }}, {{ $teacher->id }})"
                                            class="px-2 py-1.5 cursor-pointer border-l border-gray-100 hover:bg-emerald-50/70 transition-all group align-middle"
                                            title="Assign class for {{ $teacher->name }} in {{ $period->label }}"
                                        >
                                            <div class="flex items-center justify-center h-full">
                                                <span class="text-[10px] text-emerald-700 bg-emerald-50 group-hover:bg-emerald-100 px-2 py-0.5 rounded-md font-semibold transition-all flex items-center gap-1 border border-emerald-200/70 shadow-2xs">
                                                    <span class="text-xs font-bold leading-none">+</span> Assign
                                                </span>
                                            </div>
                                        </td>
                                    @endif
                                @endif
                            @endforeach

                            {{-- Lessons Sum --}}
                            <td class="px-3 py-2 text-center border-l border-indigo-100/70 bg-indigo-50/20 sticky right-0">
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full text-xs font-bold transition-all {{ $teacherPeriodCount > 0 ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-gray-100 text-gray-400' }}">
                                    {{ $teacherPeriodCount }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($periods) + 2 }}" class="px-6 py-10 text-center text-gray-400">
                                No active teachers found for the selected session.
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
                    {{-- Class Selection --}}
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

                        {{-- Conflict Notice --}}
                        @if($viewMode === 'teacher' && $classConflictNotice)
                            <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2.5 shadow-xs">
                                <div class="w-5 h-5 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div class="leading-relaxed flex-1">
                                    <div class="font-bold text-amber-950">Schedule Overwrite Notice:</div>
                                    <div class="mt-0.5 text-amber-900">
                                        <strong>{{ $classConflictNotice['class_name'] }}</strong> is already scheduled with <strong>{{ $classConflictNotice['teacher_name'] }}</strong> for <em>{{ $classConflictNotice['subject_name'] }}</em> in {{ $classConflictNotice['period_label'] }}.
                                    </div>
                                    <div class="mt-1 text-amber-700 font-medium">
                                        Saving this assignment will reassign this class slot.
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Teacher Selection --}}
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
                            <div class="p-3 {{ $modalTeacherIsCt ? 'bg-amber-50/70 border-amber-200' : 'bg-indigo-50/70 border-indigo-200' }} border rounded-xl flex items-center justify-between shadow-xs">
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
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Confirmed
                                </span>
                            </div>
                        </div>
                    @else
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
                            <p class="text-xs text-gray-400 mt-1">Filtered to teachers available in this period</p>
                        </div>
                    @endif

                    {{-- Class Teacher Sync Checkbox --}}
                    @if($selectedTeacherId && $modalClassId)
                        <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-3 transition-all">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" wire:model.live="setAsClassTeacher" class="mt-0.5 w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" />
                                <div class="flex-1 text-xs">
                                    <span class="font-semibold text-amber-900 block text-sm">
                                        Assign as Class Teacher for {{ data_get(collect($classes)->firstWhere('id', $modalClassId), 'name') }}
                                    </span>
                                    <span class="text-amber-700 mt-0.5 block leading-relaxed">
                                        Directly synchronizes with User Management and grants roll-number & attendance rights.
                                    </span>
                                </div>
                            </label>
                        </div>
                    @endif

                    {{-- Subject Selection: Clean Multi-Select Dropdown (Max 3) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Subject(s) <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-400 font-normal ml-1">(Max 3)</span>
                            </label>
                            @if(!empty($selectedSubjectIds))
                                <button 
                                    type="button" 
                                    wire:click="clearSubjects"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors"
                                >
                                    Clear all
                                </button>
                            @endif
                        </div>

                        @if(empty($modalClassId))
                            <div class="p-3 bg-gray-50 border border-dashed border-gray-200 rounded-xl text-xs text-gray-400 text-center">
                                Please select a class above first to load its available subjects.
                            </div>
                        @else
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                {{-- Dropdown Trigger Button --}}
                                <button 
                                    type="button" 
                                    @click="open = !open"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-left text-sm flex items-center justify-between focus:ring-2 focus:ring-blue-500 outline-none hover:border-gray-300 transition-colors"
                                >
                                    <div class="flex-1 truncate pr-2">
                                        @if(empty($selectedSubjectIds))
                                            <span class="text-gray-400 font-normal">-- Select Subject(s) --</span>
                                        @else
                                            @php
                                                $selectedNames = collect($availableSubjects)
                                                    ->whereIn('id', $selectedSubjectIds)
                                                    ->pluck('name')
                                                    ->implode(', ');
                                            @endphp
                                            <span class="font-semibold text-gray-800">{{ $selectedNames ?: 'Selected Subjects' }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        @if(!empty($selectedSubjectIds))
                                            <span class="text-[11px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ count($selectedSubjectIds) }}/3
                                            </span>
                                        @endif
                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-150" :class="open ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>

                                {{-- Dropdown List Menu with Simple Checkbox Rows --}}
                                <div 
                                    x-show="open" 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute left-0 right-0 z-30 mt-1 bg-white rounded-xl border border-gray-200 shadow-xl max-h-56 overflow-y-auto p-1 divide-y divide-gray-50"
                                    style="display: none;"
                                >
                                    @forelse($availableSubjects as $subject)
                                        @php
                                            $isSelected = in_array((int)$subject->id, array_map('intval', (array)$selectedSubjectIds));
                                            $isMaxReached = count($selectedSubjectIds) >= 3 && !$isSelected;
                                        @endphp
                                        <div 
                                            wire:click="toggleSubject({{ $subject->id }})"
                                            class="flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer text-sm transition-colors {{ $isSelected ? 'bg-blue-50/80 text-blue-900 font-semibold' : ($isMaxReached ? 'opacity-40 cursor-not-allowed text-gray-400' : 'text-gray-700 hover:bg-gray-50') }}"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <input 
                                                    type="checkbox" 
                                                    {{ $isSelected ? 'checked' : '' }} 
                                                    {{ $isMaxReached ? 'disabled' : '' }} 
                                                    class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 pointer-events-none w-4 h-4"
                                                />
                                                <span class="truncate">{{ $subject->name }}</span>
                                            </div>

                                            @if(!empty($subject->schedule_hint))
                                                <span class="text-[10px] text-gray-400 ml-2 font-normal whitespace-nowrap">
                                                    {{ $subject->schedule_hint }}
                                                </span>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="p-3 text-xs text-gray-400 text-center">
                                            No subjects found for this class.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <p class="text-xs text-gray-400 mt-1">
                                Click to open dropdown and pick up to 3 subjects.
                                @if(count($selectedSubjectIds) > 1)
                                    <span class="text-blue-600 font-semibold">Displays abbreviations joined with +</span>
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Room / Lab --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Room / Laboratory</label>
                        <input type="text" wire:model="room" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. Room 4, Physics Lab" />
                    </div>

                    {{-- Divided Class Option --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="isDivided" class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500" />
                            <span class="text-sm font-medium text-gray-700">Divided Class (Elective Co-teaching)</span>
                        </label>
                        <p class="text-xs text-gray-400 ml-6">For split cohorts like Bio / Computer or Arts / Agri sharing the period</p>
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

                                    {{-- Divided Slot Subject Dropdown --}}
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
                                                    {{ count($slotSubjectIds) }}/3
                                                </span>
                                            @endif
                                        </div>

                                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                            <button 
                                                type="button" 
                                                @click="open = !open"
                                                class="w-full px-3 py-1.5 rounded-lg border border-purple-200 bg-white text-left text-xs flex items-center justify-between focus:ring-2 focus:ring-purple-400 outline-none hover:border-purple-300"
                                            >
                                                <div class="flex-1 truncate pr-2">
                                                    @if(empty($slotSubjectIds))
                                                        <span class="text-gray-400">Select Subject(s)...</span>
                                                    @else
                                                        @php
                                                            $slotNames = collect($availableSubjects)->whereIn('id', $slotSubjectIds)->pluck('name')->implode(', ');
                                                        @endphp
                                                        <span class="font-medium text-gray-800">{{ $slotNames ?: 'Selected' }}</span>
                                                    @endif
                                                </div>
                                                <svg class="w-3.5 h-3.5 text-gray-400" :class="open ? 'transform rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            <div 
                                                x-show="open" 
                                                class="absolute left-0 right-0 z-30 mt-1 bg-white rounded-xl border border-purple-200 shadow-lg max-h-48 overflow-y-auto p-1 divide-y divide-purple-50"
                                                style="display: none;"
                                            >
                                                @foreach($availableSubjects as $subject)
                                                    @php
                                                        $isSlotSubSelected = in_array((int)$subject->id, array_map('intval', (array)$slotSubjectIds));
                                                        $isSlotMax = count($slotSubjectIds) >= 3 && !$isSlotSubSelected;
                                                    @endphp
                                                    <div 
                                                        wire:click="toggleDividedSubject({{ $slotIndex }}, {{ $subject->id }})"
                                                        class="flex items-center justify-between px-2.5 py-1.5 rounded-lg cursor-pointer text-xs transition-colors {{ $isSlotSubSelected ? 'bg-purple-50 text-purple-900 font-semibold' : ($isSlotMax ? 'opacity-40 cursor-not-allowed text-gray-400' : 'text-gray-700 hover:bg-gray-50') }}"
                                                    >
                                                        <div class="flex items-center gap-2 min-w-0">
                                                            <input 
                                                                type="checkbox" 
                                                                {{ $isSlotSubSelected ? 'checked' : '' }} 
                                                                {{ $isSlotMax ? 'disabled' : '' }} 
                                                                class="rounded text-purple-600 focus:ring-purple-500 border-gray-300 pointer-events-none w-3.5 h-3.5"
                                                            />
                                                            <span class="truncate">{{ $subject->name }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
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
                                    Add Another Teacher Slot ({{ count($dividedSlots) + 2 }}/5 max)
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
                            <span class="text-sm font-medium text-gray-700">Merge with Partner Section(s)</span>
                        </label>
                        <p class="text-xs text-gray-400 ml-6">Teacher will appear in merged sections' routines simultaneously</p>
                    </div>

                    @if($isMerged && $modalClassId)
                        <div class="bg-teal-50 border border-teal-100 rounded-xl p-4">
                            <p class="text-xs font-semibold text-teal-700 uppercase tracking-wide mb-3">Select Partner Section(s) to Merge</p>
                            @if($this->availableMergeClasses->isEmpty())
                                <p class="text-xs text-teal-700 italic">No other sections available to merge with.</p>
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
                                        </label>
                                    @endforeach
                                </div>
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
    {{-- PRINT TIMETABLES MODAL                                      --}}
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

            {{-- Modal Panel --}}
            <div 
                x-show="showPrintModal" 
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-lg border border-slate-200 z-10 flex flex-col my-auto"
            >
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shadow-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 leading-tight">Print Official Timetables</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Select matrix or dossier layout to auto-download PDF</p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        @click="showPrintModal = false" 
                        class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="px-6 pt-3.5 pb-0 bg-white">
                    <div class="flex p-1 bg-slate-100/80 rounded-xl text-xs font-medium text-slate-600 gap-1 border border-slate-200/60">
                        <button 
                            type="button"
                            @click="activePrintTab = 'master'"
                            :class="activePrintTab === 'master' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-800'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all"
                        >
                            Master Matrices
                        </button>
                        <button 
                            type="button"
                            @click="activePrintTab = 'class'"
                            :class="activePrintTab === 'class' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-800'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all"
                        >
                            By Class
                        </button>
                        <button 
                            type="button"
                            @click="activePrintTab = 'teacher'"
                            :class="activePrintTab === 'teacher' ? 'bg-white text-indigo-700 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-800'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all"
                        >
                            Teachers (6-Up)
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-3 max-h-[calc(85vh-160px)] overflow-y-auto">
                    {{-- 1. Master Tab --}}
                    <div x-show="activePrintTab === 'master'" class="space-y-3">
                        @if($scheduleType === 'day_wise')
                        <div class="p-3.5 rounded-xl border border-indigo-100 bg-indigo-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Select Day to Print
                                </label>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-100/70 text-indigo-700">
                                    Day-Wise Schedule
                                </span>
                            </div>
                            <select 
                                x-model="selectedPrintDay" 
                                class="w-full rounded-xl border border-indigo-200 text-xs text-slate-800 bg-white py-2 px-3 focus:ring-2 focus:ring-indigo-500/20 font-medium"
                            >
                                <option value="all">🗓️ All Working Days (Sequential Timetable)</option>
                                @foreach($days as $day)
                                    <option value="{{ $day }}">📅 {{ $day }} Only</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-500">Choose a specific day or print all working days at once with day name clearly mentioned on every sheet.</p>
                        </div>
                        @endif

                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/40 hover:bg-white hover:border-indigo-200 transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-slate-900">Class-Wise Master Matrix</h4>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">A4 Landscape</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">Whole-school routine with classes as rows and vertical break/assembly bands.</p>
                                </div>
                                <a 
                                    :href="'{{ $scheduleType === 'day_wise' ? '/admin/schedule/print/daywise/master-classwise' : '/admin/schedule/print/master-classwise' }}?day=' + (selectedPrintDay || 'all') + '&autoprint=1'" 
                                    target="_blank"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-xs"
                                >
                                    Print Master
                                </a>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/40 hover:bg-white hover:border-indigo-200 transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-slate-900">Teacher-Wise Master Matrix</h4>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">A4 Landscape</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">Faculty timetable roster with individual periods and total lessons sum.</p>
                                </div>
                                <a 
                                    :href="'{{ $scheduleType === 'day_wise' ? '/admin/schedule/print/daywise/master-teacherwise' : '/admin/schedule/print/master-teacherwise' }}?day=' + (selectedPrintDay || 'all') + '&autoprint=1'" 
                                    target="_blank"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-xs"
                                >
                                    Print Master
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Class Tab --}}
                    <div x-show="activePrintTab === 'class'" style="display: none;" class="space-y-3">
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-white space-y-3">
                            <label class="text-xs font-bold text-slate-900 block">Choose Class</label>
                            <select 
                                x-model="selectedPrintClassId" 
                                class="w-full rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 py-2.5 px-3 focus:bg-white focus:ring-2 focus:ring-indigo-500/20"
                            >
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->class_teacher_name ? '• CT: '.$c->class_teacher_name : '' }}</option>
                                @endforeach
                            </select>
                            <div class="pt-2 flex justify-end">
                                <a 
                                    :href="'/admin/schedule/print/class/' + (selectedPrintClassId || '') + '?autoprint=1'" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-xs"
                                >
                                    Print Class Timetable
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Teachers 6-Up Tab --}}
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
                            toggleTeacher(id) {
                                const idStr = String(id);
                                const idx = this.selectedTeacherIds.findIndex(i => String(i) === idStr);
                                if (idx > -1) {
                                    this.selectedTeacherIds.splice(idx, 1);
                                } else {
                                    this.selectedTeacherIds.push(id);
                                }
                            },
                            selectAll() {
                                this.selectedTeacherIds = this.teachersList.map(t => t.id);
                            },
                            clearAll() {
                                this.selectedTeacherIds = [];
                            }
                        }"
                    >
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-white space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-900">Select Teachers</label>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">6 Cards / Page</span>
                            </div>

                            <div class="relative" @click.outside="open = false">
                                <div 
                                    @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                    class="min-h-[42px] w-full rounded-xl border border-slate-200 text-xs bg-slate-50 py-1.5 px-3 cursor-pointer flex items-center justify-between gap-2"
                                >
                                    <span class="text-slate-700 font-medium" x-text="selectedTeacherIds.length + ' teacher(s) selected'"></span>
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>

                                <div 
                                    x-show="open" 
                                    class="absolute left-0 right-0 z-50 mt-1.5 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden"
                                    style="display: none;"
                                >
                                    <div class="p-2 border-b border-slate-100 bg-slate-50">
                                        <input 
                                            x-ref="searchInput"
                                            type="text" 
                                            x-model="search" 
                                            placeholder="Search teacher..." 
                                            class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        />
                                        <div class="flex justify-between items-center mt-2 px-1 text-[11px]">
                                            <button type="button" @click="selectAll()" class="text-indigo-600 font-semibold">Select All</button>
                                            <button type="button" @click="clearAll()" class="text-rose-600 font-medium">Clear</button>
                                        </div>
                                    </div>
                                    <div class="max-h-48 overflow-y-auto p-1 divide-y divide-slate-50">
                                        <template x-for="t in filteredTeachers" :key="'t-' + t.id">
                                            <div 
                                                @click="toggleTeacher(t.id)" 
                                                class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-indigo-50/70 cursor-pointer text-xs"
                                            >
                                                <span x-text="t.name" class="font-medium text-slate-800"></span>
                                                <input 
                                                    type="checkbox" 
                                                    :checked="selectedTeacherIds.some(i => String(i) === String(t.id))" 
                                                    class="rounded text-indigo-600 pointer-events-none"
                                                />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <a 
                                    :href="'/admin/schedule/print/teachers-bulk?teachers=' + selectedTeacherIds.join(',') + '&autoprint=1'" 
                                    target="_blank"
                                    :class="selectedTeacherIds.length > 0 ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-slate-200 text-slate-400 pointer-events-none'"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold transition-all shadow-xs"
                                >
                                    Print Dossier (<span x-text="selectedTeacherIds.length"></span>)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-[11px] text-slate-400">PDFs auto-download with native print dialogue</span>
                    <button 
                        type="button" 
                        @click="showPrintModal = false"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-200/60"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>