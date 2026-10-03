<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <span>Teacher Attendance &amp; Substitutions</span>
            </h1>
            <p class="text-gray-500 text-sm mt-0.5">Manage daily teacher attendance, assign period arrangements, and view monthly registers</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
            {{-- Daily Arrangement PDF Download --}}
            <a 
                href="{{ $this->getPrintUrl() }}" 
                target="_blank"
                class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium flex items-center gap-2 shadow-sm shadow-blue-200 transition-all text-sm"
                title="Open in new tab and auto-download arrangement PDF"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Download Arrangement PDF</span>
            </a>

            {{-- Monthly Register Print --}}
            <a 
                href="{{ $this->getMonthlyPrintUrl() }}" 
                target="_blank" 
                class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-medium flex items-center gap-2 shadow-sm shadow-indigo-200 transition-all text-sm"
                title="Print or view full monthly teacher attendance register"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Monthly Register</span>
            </a>
        </div>
    </div>

    {{-- Session / Status Alerts --}}
    @if(session()->has('message'))
        <div class="bg-emerald-600 border border-emerald-700 text-white font-semibold p-3.5 rounded-xl shadow-md shadow-emerald-200 flex items-center gap-2.5">
            <svg class="w-5 h-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if($warningMessage)
        <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl text-amber-800 font-medium flex items-center gap-2 text-sm shadow-sm">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ $warningMessage }}
        </div>
    @endif

    {{-- Main 3-Tab Navigation Bar --}}
    <div class="bg-white rounded-2xl p-1.5 shadow-sm border border-gray-100 flex flex-wrap gap-1 sm:gap-2">
        <button 
            wire:click="setTab('attendance')" 
            class="flex-1 min-w-[140px] py-2.5 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 {{ $activeTab === 'attendance' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span>Daily Attendance &amp; Remarks</span>
        </button>

        <button 
            wire:click="setTab('arrangement')" 
            class="flex-1 min-w-[140px] py-2.5 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 relative {{ $activeTab === 'arrangement' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <span>Arrangement Overview</span>
            @php
                $nonPresentCount = collect($teachers)->filter(fn($t) => ($teacherStatuses[$t->id] ?? 'Present') !== 'Present')->count();
            @endphp
            @if($nonPresentCount > 0)
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $activeTab === 'arrangement' ? 'bg-white text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                    {{ $nonPresentCount }}
                </span>
            @endif
        </button>

        <button 
            wire:click="setTab('reports')" 
            class="flex-1 min-w-[140px] py-2.5 px-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 {{ $activeTab === 'reports' ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span>Reports &amp; Workload</span>
        </button>
    </div>

    {{-- ============================================================ --}}
    {{-- TAB 1: DAILY ATTENDANCE & REMARKS                            --}}
    {{-- ============================================================ --}}
    @if($activeTab === 'attendance')
        <div class="glass-card rounded-2xl p-4 sm:p-6 space-y-6">
            {{-- Date & Session Controls Bar --}}
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Attendance Date</label>
                        <input type="date" wire:model.live="selectedDate" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm font-medium shadow-sm">
                    </div>
                    <div class="sm:mt-5 text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-2 rounded-xl border border-gray-150">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                    </div>
                    @if(count($academicSessions) > 1)
                        <div class="w-full sm:w-auto">
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Session</label>
                            <select wire:model.live="selectedSessionId" class="px-3 py-2 border border-gray-200 rounded-xl text-sm font-medium bg-white outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($academicSessions as $session)
                                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                {{-- Action Buttons: Close Class, Merge Class, Mark All Present --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Close Class Button --}}
                    <button 
                        type="button"
                        wire:click="openCloseClassModal" 
                        class="px-3.5 py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 rounded-xl font-semibold text-sm flex items-center gap-2 shadow-sm transition-all"
                        title="Close one or more classes for today"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Close Class</span>
                        @if(count($closedClassesList) > 0)
                            <span class="px-1.5 py-0.2 bg-rose-600 text-white rounded-full text-xs font-bold">
                                {{ count($closedClassesList) }}
                            </span>
                        @endif
                    </button>

                    {{-- Merge Class Button --}}
                    <button 
                        type="button"
                        wire:click="openMergeModal" 
                        class="px-3.5 py-2 bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 rounded-xl font-semibold text-sm flex items-center gap-2 shadow-sm transition-all"
                        title="Merge two classes for today's periods"
                    >
                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span>Merge Class</span>
                        @if(count($mergedClassesList) > 0)
                            <span class="px-1.5 py-0.2 bg-purple-600 text-white rounded-full text-xs font-bold">
                                {{ count($mergedClassesList) }}
                            </span>
                        @endif
                    </button>

                    {{-- Mark All Present Button --}}
                    <button 
                        type="button"
                        wire:click="markAllPresent" 
                        wire:confirm="Are you sure you want to mark all teachers as Present for today?"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 font-semibold text-sm flex items-center gap-2 shadow-sm shadow-emerald-200 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Mark All Present</span>
                    </button>
                </div>
            </div>

            {{-- Quick Attendance Stats Bar --}}
            @php
                $totalT = count($teachers);
                $pCount = 0; $lCount = 0; $odCount = 0; $slCount = 0; $aCount = 0;
                foreach ($teachers as $t) {
                    $st = $teacherStatuses[$t->id] ?? 'Present';
                    if ($st === 'Present') $pCount++;
                    elseif ($st === 'Leave') $lCount++;
                    elseif ($st === 'Official Duty') $odCount++;
                    elseif ($st === 'Short Leave') $slCount++;
                    elseif ($st === 'Absent') $aCount++;
                }
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="p-3 bg-gray-50 border border-gray-150 rounded-xl text-center">
                    <div class="text-xs font-semibold text-gray-500">Total Staff</div>
                    <div class="text-xl font-extrabold text-gray-800 mt-0.5">{{ $totalT }}</div>
                </div>
                <div class="p-3 bg-green-50 border border-green-200 rounded-xl text-center">
                    <div class="text-xs font-semibold text-green-700">Present</div>
                    <div class="text-xl font-extrabold text-green-800 mt-0.5">{{ $pCount }}</div>
                </div>
                <div class="p-3 bg-orange-50 border border-orange-200 rounded-xl text-center">
                    <div class="text-xs font-semibold text-orange-700">Leave</div>
                    <div class="text-xl font-extrabold text-orange-800 mt-0.5">{{ $lCount }}</div>
                </div>
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-center">
                    <div class="text-xs font-semibold text-blue-700">Official Duty</div>
                    <div class="text-xl font-extrabold text-blue-800 mt-0.5">{{ $odCount }}</div>
                </div>
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-center">
                    <div class="text-xs font-semibold text-amber-700">Short Leave</div>
                    <div class="text-xl font-extrabold text-amber-800 mt-0.5">{{ $slCount }}</div>
                </div>
                <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-center">
                    <div class="text-xs font-semibold text-red-700">Absent</div>
                    <div class="text-xl font-extrabold text-red-800 mt-0.5">{{ $aCount }}</div>
                </div>
            </div>

            {{-- Closed & Merged Classes Active Notification Banner --}}
            @if(count($closedClassesList) > 0 || count($mergedClassesList) > 0)
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        @if(count($closedClassesList) > 0)
                            <div class="flex items-center gap-1.5 bg-rose-100 text-rose-800 px-2.5 py-1 rounded-lg font-semibold">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>{{ count($closedClassesList) }} Closed Class(es):</span>
                                <span class="font-normal">{{ implode(', ', array_column($closedClassesList, 'class_name')) }}</span>
                            </div>
                        @endif
                        @if(count($mergedClassesList) > 0)
                            <div class="flex items-center gap-1.5 bg-purple-100 text-purple-800 px-2.5 py-1 rounded-lg font-semibold">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                <span>{{ count($mergedClassesList) }} Merged Class Pair(s):</span>
                                <span class="font-normal">
                                    @foreach($mergedClassesList as $m)
                                        {{ $m['source_class_name'] }} &rarr; {{ $m['target_class_name'] }}{{ !$loop->last ? '; ' : '' }}
                                    @endforeach
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if(count($closedClassesList) > 0)
                            <button type="button" wire:click="openCloseClassModal" class="text-rose-700 hover:text-rose-900 font-bold underline">Manage Closed</button>
                        @endif
                        @if(count($mergedClassesList) > 0)
                            <button type="button" wire:click="openMergeModal" class="text-purple-700 hover:text-purple-900 font-bold underline">Manage Merges</button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Daily Attendance Table --}}
            <div class="border border-gray-150 rounded-2xl overflow-hidden shadow-sm bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/75 border-b border-gray-150 text-gray-600 font-semibold uppercase text-xs tracking-wider">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4 min-w-[180px]">Teacher</th>
                                <th class="py-3 px-4 min-w-[320px]">Status</th>
                                <th class="py-3 px-4 min-w-[220px]">Remarks / Note</th>
                                <th class="py-3 px-4 w-40 text-center">Arrangement</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($teachers as $index => $teacher)
                                @php
                                    $currentStatus = $teacherStatuses[$teacher->id] ?? 'Present';
                                    $isNonPresent  = ($currentStatus !== 'Present');
                                    $tSchedule     = $this->getTeacherSchedule($teacher->id);
                                    $schedCount    = $tSchedule->count();
                                @endphp
                                <tr wire:key="teacher-att-{{ $teacher->id }}" class="hover:bg-gray-50/60 transition-colors {{ $isNonPresent ? 'bg-blue-50/30' : '' }}">
                                    {{-- Index --}}
                                    <td class="py-3 px-4 text-center text-xs text-gray-400 font-medium">
                                        {{ $index + 1 }}
                                    </td>

                                    {{-- Teacher Name & Email --}}
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-gray-900 text-sm flex items-center gap-1.5">
                                            <span>{{ $teacher->name }}</span>
                                            @if(($dailySubCounts[$teacher->id] ?? 0) > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700" title="{{ $dailySubCounts[$teacher->id] }} substitute duty today">
                                                    {{ $dailySubCounts[$teacher->id] }} Sub{{ $dailySubCounts[$teacher->id] > 1 ? 's' : '' }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($teacher->email)
                                            <div class="text-xs text-gray-400 font-normal truncate max-w-[180px]">{{ $teacher->email }}</div>
                                        @endif
                                    </td>

                                    {{-- Status Radio Pills --}}
                                    <td class="py-3 px-4">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            {{-- Present --}}
                                            <label class="px-2.5 py-1 rounded-lg cursor-pointer text-xs font-semibold transition-all border {{ $currentStatus === 'Present' ? 'bg-green-600 text-white border-green-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Present" class="hidden">
                                                Present
                                            </label>

                                            {{-- Leave --}}
                                            <label class="px-2.5 py-1 rounded-lg cursor-pointer text-xs font-semibold transition-all border {{ $currentStatus === 'Leave' ? 'bg-orange-500 text-white border-orange-500 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Leave" class="hidden">
                                                Leave
                                            </label>

                                            {{-- Official Duty --}}
                                            <label class="px-2.5 py-1 rounded-lg cursor-pointer text-xs font-semibold transition-all border {{ $currentStatus === 'Official Duty' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Official Duty" class="hidden">
                                                Official Duty
                                            </label>

                                            {{-- Short Leave --}}
                                            <label class="px-2.5 py-1 rounded-lg cursor-pointer text-xs font-semibold transition-all border {{ $currentStatus === 'Short Leave' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Short Leave" class="hidden">
                                                Short Leave
                                            </label>

                                            {{-- Absent --}}
                                            <label class="px-2.5 py-1 rounded-lg cursor-pointer text-xs font-semibold transition-all border {{ $currentStatus === 'Absent' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Absent" class="hidden">
                                                Absent
                                            </label>
                                        </div>
                                    </td>

                                    {{-- Inline Remarks --}}
                                    <td class="py-3 px-4">
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                wire:model.blur="teacherRemarks.{{ $teacher->id }}" 
                                                placeholder="Add remark (e.g. sick leave, meeting)..."
                                                class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none bg-white text-gray-800 placeholder-gray-400 transition-all"
                                            >
                                            <span wire:loading wire:target="teacherRemarks.{{ $teacher->id }}" class="absolute right-2 top-2 text-[10px] text-blue-500 font-semibold">
                                                Saving...
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Arrangement Shortcut --}}
                                    <td class="py-3 px-4 text-center">
                                        @if($isNonPresent)
                                            <button 
                                                type="button"
                                                wire:click="openArrangementForTeacher({{ $teacher->id }})" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs rounded-xl border border-blue-200 transition-all"
                                                title="Open substitute arrangement for this teacher"
                                            >
                                                <span>Assign Subs</span>
                                                @if($schedCount > 0)
                                                    <span class="px-1.5 py-0.2 bg-blue-600 text-white text-[10px] rounded-full font-bold">
                                                        {{ $schedCount }}
                                                    </span>
                                                @endif
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 font-medium">On Duty</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-400 text-sm">
                                        No teachers registered for the current shift.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB 2: ARRANGEMENT OVERVIEW (SUBSTITUTIONS)                  --}}
    {{-- ============================================================ --}}
    @if($activeTab === 'arrangement')
        <div class="space-y-6">
            {{-- Arrangement Header & Date Switcher --}}
            <div class="glass-card rounded-2xl p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Arrangement Date</label>
                            <input type="date" wire:model.live="selectedDate" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm font-medium">
                        </div>
                        <div class="sm:mt-5 text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-2 rounded-xl border border-gray-150">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                        </div>
                    </div>

                    {{-- Quick indicator & Download PDF action --}}
                    @php
                        $nonPresentTeachers = collect($teachers)->filter(fn($t) => ($teacherStatuses[$t->id] ?? 'Present') !== 'Present');
                    @endphp
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-sm text-gray-600">
                            <strong>{{ $nonPresentTeachers->count() }}</strong> teacher(s) absent / on leave requiring class coverage today.
                        </div>
                        <a 
                            href="{{ $this->getPrintUrl() }}" 
                            target="_blank"
                            class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm shadow-blue-200 transition-all"
                            title="Open in new tab and auto-download arrangement PDF for selected date"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Download Arrangement PDF</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Cards for Absent/Leave Teachers --}}
            <div class="space-y-4">
                @forelse($nonPresentTeachers as $teacher)
                    @php
                        $status = $teacherStatuses[$teacher->id] ?? 'Absent';
                        $schedule = $this->getTeacherSchedule($teacher->id);
                        $remark = $teacherRemarks[$teacher->id] ?? '';
                        $isFocused = ($focusedTeacherId == $teacher->id);
                    @endphp
                    <div wire:key="arrangement-card-{{ $teacher->id }}" class="border rounded-2xl overflow-hidden shadow-sm transition-all {{ $isFocused ? 'ring-2 ring-blue-500 border-blue-400' : 'border-gray-200 bg-white' }}">
                        {{-- Card Header --}}
                        <div class="p-4 bg-gray-50/80 border-b border-gray-150 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h3 class="text-base sm:text-lg font-bold text-gray-800">{{ $teacher->name }}</h3>
                                
                                {{-- Status Badge --}}
                                @if($status === 'Leave')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">Leave</span>
                                @elseif($status === 'Official Duty')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">Official Duty</span>
                                @elseif($status === 'Short Leave')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">Short Leave</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">Absent</span>
                                @endif

                                {{-- Remarks if any --}}
                                @if(!empty($remark))
                                    <span class="text-xs text-gray-500 italic bg-white px-2 py-0.5 rounded-md border border-gray-200">
                                        &ldquo;{{ $remark }}&rdquo;
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold px-2.5 py-1 bg-white rounded-lg border border-gray-200 text-gray-600">
                                    {{ $schedule->count() }} Class Period{{ $schedule->count() != 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>

                        {{-- Scheduled Periods for this teacher --}}
                        <div class="p-4 bg-white">
                            @if($schedule->isEmpty())
                                <div class="text-gray-400 text-sm text-center py-6">
                                    No regular classes scheduled for this teacher on {{ \Carbon\Carbon::parse($selectedDate)->format('l') }}.
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($schedule as $period)
                                        @php
                                            $showAll = $showAllTeachersToggle[$teacher->id][$period->period_no] ?? false;
                                            $currentSub = $substitutions[$teacher->id][$period->period_no] ?? null;
                                            $availableList = $showAll 
                                                ? $teachers 
                                                : $this->getAvailableTeachersForPeriod($period->period_no, $currentSub, $period->class_id);
                                        @endphp
                                        <div wire:key="period-{{ $teacher->id }}-{{ $period->period_no }}" class="flex flex-col lg:flex-row lg:items-center justify-between p-4 bg-gray-50/70 rounded-xl border border-gray-150 gap-4">
                                            {{-- Period Info --}}
                                            <div class="w-full lg:w-1/3">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded-md">
                                                        Period {{ $period->period_no }}
                                                    </span>
                                                    <span class="font-extrabold text-gray-900 text-base">
                                                        {{ $period->class_name }}
                                                    </span>
                                                </div>
                                                <div class="text-sm font-medium text-gray-600 mt-1">
                                                    {{ $period->subject_name }}
                                                </div>
                                            </div>

                                            {{-- Substitute Assignment Area --}}
                                            <div class="w-full lg:flex-1 flex flex-col sm:flex-row sm:items-center gap-3">
                                                @if(!empty($period->is_closed))
                                                    <div class="w-full p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between">
                                                        <div class="flex items-center gap-2 text-rose-800 text-xs font-bold">
                                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                            </svg>
                                                            <span>Class Closed for Today: {{ $period->closed_reason ?: 'Closed' }}</span>
                                                        </div>
                                                        <span class="text-[11px] text-rose-700 font-bold bg-rose-100 px-2 py-0.5 rounded-md border border-rose-200">No Sub Needed</span>
                                                    </div>
                                                @elseif(!empty($period->is_merged_away))
                                                    <div class="w-full p-3 bg-purple-50 border border-purple-200 rounded-xl flex items-center justify-between">
                                                        <div class="flex items-center gap-2 text-purple-800 text-xs font-bold">
                                                            <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                            </svg>
                                                            <span>Combined with {{ $period->merged_with_class_name }}</span>
                                                        </div>
                                                        <span class="text-[11px] text-purple-700 font-bold bg-purple-100 px-2 py-0.5 rounded-md border border-purple-200">Merged Session</span>
                                                    </div>
                                                @else
                                                    <div class="flex-1">
                                                        {{-- CLEAN SELECTOR: Only Teacher Names inside <option> tag --}}
                                                        <select 
                                                            wire:model="substitutions.{{ $teacher->id }}.{{ $period->period_no }}"
                                                            wire:change="assignSubstitute({{ $teacher->id }}, {{ $period->period_no }}, {{ $period->class_id }}, {{ $period->subject_id }})"
                                                            class="w-full px-4 py-2 border {{ $showAll ? 'border-amber-400 focus:ring-amber-500' : 'border-gray-200 focus:ring-blue-500' }} rounded-xl outline-none bg-white text-sm font-semibold transition-all shadow-sm"
                                                        >
                                                            <option value="">-- Assign Substitute --</option>
                                                            @foreach($availableList as $t)
                                                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                            @endforeach
                                                        </select>

                                                        {{-- VISUAL BADGES / WORKLOAD OUTSIDE SELECTOR (Clean UI) --}}
                                                        @if($currentSub)
                                                            @php
                                                                $selDaily   = $dailySubCounts[$currentSub] ?? 0;
                                                                $selMonthly = $monthlySubCounts[$currentSub] ?? 0;
                                                                $selTeacher = collect($teachers)->firstWhere('id', $currentSub);
                                                                $selName    = $selTeacher?->name ?? 'Teacher';
                                                                $assignedDuties = $teacherAssignedSubs[$currentSub] ?? [];

                                                                if ($selDaily >= 3)      $loadBadge = 'text-red-700 bg-red-50 border-red-200';
                                                                elseif ($selDaily == 2)  $loadBadge = 'text-amber-800 bg-amber-50 border-amber-200';
                                                                else                     $loadBadge = 'text-emerald-700 bg-emerald-50 border-emerald-200';
                                                            @endphp
                                                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                                                {{-- Workload Badge --}}
                                                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-semibold {{ $loadBadge }}">
                                                                    <span class="w-2 h-2 rounded-full {{ $selDaily >= 3 ? 'bg-red-500' : ($selDaily == 2 ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                                                    <span class="font-bold">{{ $selDaily }} sub{{ $selDaily != 1 ? 's' : '' }} today</span>
                                                                    @if($showMonthlyCount)
                                                                        <span class="text-gray-400">&bull;</span>
                                                                        <span class="font-normal text-gray-600">{{ $selMonthly }} this month</span>
                                                                    @endif
                                                                </div>

                                                                {{-- Extra period duties badge --}}
                                                                @if(count($assignedDuties) > 1)
                                                                    @php
                                                                        $otherDuties = collect($assignedDuties)->filter(fn($d) => !($d['period_no'] == $period->period_no && $d['class_name'] == $period->class_name));
                                                                    @endphp
                                                                    @if($otherDuties->isNotEmpty())
                                                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 rounded-lg border border-blue-150 text-[11px] font-medium" title="Other substitute periods today">
                                                                            <span>Also:</span>
                                                                            @foreach($otherDuties as $duty)
                                                                                <span class="font-bold">{{ $duty['class_name'] }} (P{{ $duty['period_no'] }})</span>{{ !$loop->last ? ',' : '' }}
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        @endif

                                                        {{-- Override warning badge --}}
                                                        @if($showAll)
                                                            <div class="text-[11px] text-amber-700 font-medium mt-1.5 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                                </svg>
                                                                <span>Showing all teachers. Assignment may cause timetable conflict.</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Override Checkbox --}}
                                                    <div class="w-full sm:w-auto shrink-0">
                                                        <label class="flex items-center gap-2 cursor-pointer group bg-white px-3 py-2 rounded-xl border border-gray-200 hover:border-gray-300 transition-all">
                                                            <input 
                                                                type="checkbox" 
                                                                wire:model.live="showAllTeachersToggle.{{ $teacher->id }}.{{ $period->period_no }}" 
                                                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                            >
                                                            <span class="text-xs font-semibold text-gray-600 group-hover:text-gray-800">Show All (Override)</span>
                                                        </label>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="glass-card rounded-2xl p-12 text-center">
                        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">All Teachers are Present!</h3>
                        <p class="text-gray-500 text-sm max-w-md mx-auto mt-1">
                            No teachers are marked absent or on leave for {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}. No class period arrangements are required.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB 3: REPORTS & WORKLOAD                                    --}}
    {{-- ============================================================ --}}
    @if($activeTab === 'reports')
        <div class="space-y-6">
            {{-- Sub-Tab Navigation Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-2 rounded-2xl border border-gray-150 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <button 
                        type="button"
                        wire:click="setReportTab('monthly_attendance')" 
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $reportTab === 'monthly_attendance' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                    >
                        Monthly Register
                    </button>
                    <button 
                        type="button"
                        wire:click="setReportTab('teacher_report')" 
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $reportTab === 'teacher_report' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                    >
                        Teacher Attendance &amp; Remarks Report
                    </button>
                    <button 
                        type="button"
                        wire:click="setReportTab('workload')" 
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $reportTab === 'workload' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                    >
                        Workload Distribution
                    </button>
                </div>

                {{-- Month selector (for Monthly Attendance) --}}
                @if($reportTab === 'monthly_attendance')
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Select Month:</label>
                        <input 
                            type="month" 
                            wire:model.live="selectedMonth" 
                            class="px-3 py-1.5 border border-gray-200 rounded-xl text-sm font-bold bg-white focus:ring-2 focus:ring-indigo-500 outline-none"
                        >
                        <a 
                            href="{{ $this->getMonthlyPrintUrl() }}" 
                            target="_blank" 
                            class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs font-bold border border-indigo-200 transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Print Register</span>
                        </a>
                    </div>
                @endif
            </div>

            {{-- ------------------------------------------------------------ --}}
            {{-- SUB-TAB: MONTHLY ATTENDANCE REGISTER                         --}}
            {{-- ------------------------------------------------------------ --}}
            @if($reportTab === 'monthly_attendance')
                {{-- KPI Summary Cards --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-4 bg-white border border-gray-150 rounded-2xl shadow-sm text-center">
                        <div class="text-xs font-semibold text-gray-500">Staff Count</div>
                        <div class="text-2xl font-extrabold text-gray-800 mt-1">{{ $monthlyStats['total_teachers'] }}</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-150 rounded-2xl shadow-sm text-center">
                        <div class="text-xs font-semibold text-gray-500">Working Days</div>
                        <div class="text-2xl font-extrabold text-indigo-700 mt-1">{{ $monthlyStats['working_days'] }}</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-150 rounded-2xl shadow-sm text-center">
                        <div class="text-xs font-semibold text-gray-500">Avg Attendance</div>
                        <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $monthlyStats['avg_attendance'] }}%</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-150 rounded-2xl shadow-sm text-center">
                        <div class="text-xs font-semibold text-gray-500">Total Leaves</div>
                        <div class="text-2xl font-extrabold text-orange-600 mt-1">{{ $monthlyStats['total_leaves'] }}</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-150 rounded-2xl shadow-sm text-center">
                        <div class="text-xs font-semibold text-gray-500">Total Absences</div>
                        <div class="text-2xl font-extrabold text-red-600 mt-1">{{ $monthlyStats['total_absences'] }}</div>
                    </div>
                    <div class="p-4 bg-white border border-gray-150 rounded-2xl shadow-sm text-center">
                        <div class="text-xs font-semibold text-gray-500">Substitutions</div>
                        <div class="text-2xl font-extrabold text-purple-600 mt-1">{{ $monthlyStats['total_substitutions'] }}</div>
                    </div>
                </div>

                {{-- Monthly Attendance Matrix Table --}}
                <div class="bg-white border border-gray-150 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-gray-50/75 border-b border-gray-150 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-gray-800">
                                Staff Attendance Matrix &mdash; {{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('F Y') }}
                            </h3>
                            <p class="text-xs text-gray-500">Click on any staff member's name to view their individual attendance history and remarks</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Present</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span> Leave</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> OD</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Short</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Absent</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase tracking-wider border-b border-gray-150">
                                <tr>
                                    <th class="py-3 px-3 w-10 text-center">#</th>
                                    <th class="py-3 px-3 min-w-[160px]">Teacher</th>
                                    <th class="py-3 px-2 text-center text-emerald-700 bg-emerald-50/50">P</th>
                                    <th class="py-3 px-2 text-center text-orange-700 bg-orange-50/50">L</th>
                                    <th class="py-3 px-2 text-center text-blue-700 bg-blue-50/50">OD</th>
                                    <th class="py-3 px-2 text-center text-amber-700 bg-amber-50/50">SL</th>
                                    <th class="py-3 px-2 text-center text-red-700 bg-red-50/50">A</th>
                                    <th class="py-3 px-2 text-center text-purple-700 bg-purple-50/50">Sub</th>
                                    <th class="py-3 px-3 text-center min-w-[90px]">Rate %</th>
                                    {{-- Day Columns --}}
                                    @foreach($monthlyDays as $dayInfo)
                                        <th class="py-2 px-1 text-center font-bold {{ $dayInfo['is_weekend'] ? 'bg-gray-100 text-gray-400' : ($dayInfo['is_holiday'] ? 'bg-purple-50 text-purple-600' : 'text-gray-700') }}" title="{{ $dayInfo['date'] }} ({{ $dayInfo['day_name'] }})">
                                            <div>{{ $dayInfo['day'] }}</div>
                                            <div class="text-[9px] font-normal lowercase">{{ substr($dayInfo['day_name'], 0, 1) }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($monthlyAttendanceMatrix as $idx => $tRow)
                                    <tr wire:key="matrix-row-{{ $tRow['teacher_id'] }}" class="hover:bg-gray-50/70 transition-colors">
                                        <td class="py-2.5 px-3 text-center text-gray-400 font-medium">{{ $idx + 1 }}</td>
                                        <td class="py-2.5 px-3 whitespace-nowrap">
                                            <button 
                                                type="button" 
                                                wire:click="selectTeacherForReport({{ $tRow['teacher_id'] }})"
                                                class="font-bold text-gray-800 hover:text-indigo-600 transition-colors flex items-center gap-1.5 group text-left"
                                                title="View detailed attendance & remarks report for {{ $tRow['name'] }}"
                                            >
                                                <span>{{ $tRow['name'] }}</span>
                                                <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 text-indigo-500 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="py-2.5 px-2 text-center font-bold text-emerald-700 bg-emerald-50/30">{{ $tRow['present'] }}</td>
                                        <td class="py-2.5 px-2 text-center font-bold text-orange-700 bg-orange-50/30">{{ $tRow['leave'] }}</td>
                                        <td class="py-2.5 px-2 text-center font-bold text-blue-700 bg-blue-50/30">{{ $tRow['official_duty'] }}</td>
                                        <td class="py-2.5 px-2 text-center font-bold text-amber-700 bg-amber-50/30">{{ $tRow['short_leave'] }}</td>
                                        <td class="py-2.5 px-2 text-center font-bold text-red-700 bg-red-50/30">{{ $tRow['absent'] }}</td>
                                        <td class="py-2.5 px-2 text-center font-bold text-purple-700 bg-purple-50/30">{{ $tRow['substitutions'] }}</td>
                                        <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                            @php
                                                $pct = $tRow['percentage'];
                                                if ($pct >= 90)     $pctCls = 'text-emerald-700 bg-emerald-100';
                                                elseif ($pct >= 75) $pctCls = 'text-blue-700 bg-blue-100';
                                                elseif ($pct >= 60) $pctCls = 'text-amber-700 bg-amber-100';
                                                else                $pctCls = 'text-red-700 bg-red-100';
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full font-bold text-[11px] {{ $pctCls }}">
                                                {{ $pct }}%
                                            </span>
                                        </td>
                                        {{-- 1-31 Day Cells (Clean: No remarks in register as requested) --}}
                                        @foreach($monthlyDays as $dayInfo)
                                            @php
                                                $dayNum = $dayInfo['day'];
                                                $rec = $tRow['days'][$dayNum] ?? null;
                                                $cellStatus = $rec['code'] ?? ($rec['status'] ?? '-');

                                                if ($cellStatus === 'P') {
                                                    $cellBg = 'bg-emerald-100 text-emerald-800 font-bold';
                                                } elseif ($cellStatus === 'L') {
                                                    $cellBg = 'bg-orange-100 text-orange-800 font-bold';
                                                } elseif ($cellStatus === 'OD') {
                                                    $cellBg = 'bg-blue-100 text-blue-800 font-bold';
                                                } elseif ($cellStatus === 'SL') {
                                                    $cellBg = 'bg-amber-100 text-amber-800 font-bold';
                                                } elseif ($cellStatus === 'A') {
                                                    $cellBg = 'bg-red-100 text-red-800 font-bold';
                                                } elseif ($cellStatus === 'H') {
                                                    $cellBg = 'bg-purple-100 text-purple-700 font-semibold';
                                                } elseif ($cellStatus === 'W') {
                                                    $cellBg = 'bg-gray-100 text-gray-400 font-normal';
                                                } else {
                                                    $cellBg = 'text-gray-300';
                                                }
                                            @endphp
                                            <td class="py-1 px-1 text-center {{ $dayInfo['is_weekend'] ? 'bg-gray-50/50' : '' }}" title="{{ $dayInfo['date'] }}: {{ $cellStatus }}">
                                                <div class="w-6 h-6 mx-auto rounded flex items-center justify-center text-[10px] {{ $cellBg }}">
                                                    {{ $cellStatus }}
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($monthlyDays) + 9 }}" class="py-8 text-center text-gray-400 text-sm">
                                            No teacher attendance data found for this month.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ------------------------------------------------------------ --}}
            {{-- SUB-TAB: TEACHER ATTENDANCE & REMARKS REPORT                 --}}
            {{-- ------------------------------------------------------------ --}}
            @if($reportTab === 'teacher_report')
                @php
                    $teacherDetails = $this->getSelectedTeacherMonthlyDetails();
                    $selTeacherObj = $teacherDetails['teacher'] ?? null;
                    $selSummary = $teacherDetails['summary'] ?? [];
                    $selDays = $teacherDetails['days'] ?? [];
                @endphp
                <div class="space-y-6">
                    {{-- Controls Bar: Teacher Selector, Month, Print --}}
                    <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Select Teacher</label>
                                <select wire:model.live="selectedTeacherId" class="px-3.5 py-2 border border-gray-200 rounded-xl text-sm font-bold bg-white focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm min-w-[200px]">
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Month</label>
                                <input 
                                    type="month" 
                                    wire:model.live="selectedMonth" 
                                    class="px-3.5 py-2 border border-gray-200 rounded-xl text-sm font-bold bg-white focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm"
                                >
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($selTeacherObj)
                                <a 
                                    href="{{ $this->getTeacherPrintUrl() }}" 
                                    target="_blank" 
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md shadow-indigo-100 transition-all flex items-center gap-2"
                                    title="Print or download this teacher's attendance dossier"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    <span>Print Teacher Report</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($selTeacherObj)
                        {{-- Teacher KPI Stats Banner --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
                                <div class="text-xs font-semibold text-emerald-700">Present</div>
                                <div class="text-2xl font-extrabold text-emerald-800 mt-0.5">{{ $selSummary['present'] ?? 0 }}</div>
                            </div>
                            <div class="p-3.5 bg-orange-50 border border-orange-200 rounded-xl text-center">
                                <div class="text-xs font-semibold text-orange-700">Leaves</div>
                                <div class="text-2xl font-extrabold text-orange-800 mt-0.5">{{ $selSummary['leave'] ?? 0 }}</div>
                            </div>
                            <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-center">
                                <div class="text-xs font-semibold text-blue-700">Official Duty</div>
                                <div class="text-2xl font-extrabold text-blue-800 mt-0.5">{{ $selSummary['official_duty'] ?? 0 }}</div>
                            </div>
                            <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-center">
                                <div class="text-xs font-semibold text-amber-700">Short Leaves</div>
                                <div class="text-2xl font-extrabold text-amber-800 mt-0.5">{{ $selSummary['short_leave'] ?? 0 }}</div>
                            </div>
                            <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-center">
                                <div class="text-xs font-semibold text-red-700">Absent</div>
                                <div class="text-2xl font-extrabold text-red-800 mt-0.5">{{ $selSummary['absent'] ?? 0 }}</div>
                            </div>
                            <div class="p-3.5 bg-purple-50 border border-purple-200 rounded-xl text-center">
                                <div class="text-xs font-semibold text-purple-700">Substitutions</div>
                                <div class="text-2xl font-extrabold text-purple-800 mt-0.5">{{ $selSummary['substitutions'] ?? 0 }}</div>
                            </div>
                            <div class="p-3.5 bg-indigo-50 border border-indigo-200 rounded-xl text-center col-span-2 sm:col-span-1">
                                <div class="text-xs font-semibold text-indigo-700">Attendance %</div>
                                <div class="text-2xl font-extrabold text-indigo-800 mt-0.5">{{ $selSummary['percentage'] ?? 0 }}%</div>
                            </div>
                        </div>

                        {{-- Day-by-day table with Remarks --}}
                        <div class="bg-white border border-gray-150 rounded-2xl shadow-sm overflow-hidden">
                            <div class="p-4 bg-gray-50/75 border-b border-gray-150 flex items-center justify-between">
                                <div>
                                    <h4 class="text-base font-bold text-gray-800">
                                        {{ $selTeacherObj->name }} &mdash; Daily Log &amp; Remarks ({{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('F Y') }})
                                    </h4>
                                    <p class="text-xs text-gray-500">Chronological attendance status, daily remarks, and period substitution duties</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs tracking-wider border-b border-gray-150">
                                        <tr>
                                            <th class="py-3 px-4 w-12 text-center">Day</th>
                                            <th class="py-3 px-4 w-36">Date</th>
                                            <th class="py-3 px-4 w-32 text-center">Status</th>
                                            <th class="py-3 px-4 min-w-[250px]">Daily Remarks / Reason</th>
                                            <th class="py-3 px-4 min-w-[200px]">Substitutions Covered</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($selDays as $dayItem)
                                            @php
                                                $st = $dayItem['status'];
                                                if ($st === 'Present')         $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                                elseif ($st === 'Leave')       $badgeClass = 'bg-orange-100 text-orange-800 border-orange-200';
                                                elseif ($st === 'Short Leave') $badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
                                                elseif ($st === 'Official Duty')$badgeClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                                elseif ($st === 'Absent')      $badgeClass = 'bg-red-100 text-red-800 border-red-200';
                                                elseif ($st === 'Weekend')     $badgeClass = 'bg-gray-100 text-gray-500 border-gray-200';
                                                elseif ($st === 'Holiday')     $badgeClass = 'bg-purple-100 text-purple-700 border-purple-200';
                                                else                           $badgeClass = 'bg-gray-50 text-gray-400 border-gray-150';
                                            @endphp
                                            <tr class="hover:bg-gray-50/70 transition-colors {{ $dayItem['is_weekend'] ? 'bg-gray-50/40' : '' }}">
                                                <td class="py-2.5 px-4 text-center font-bold text-gray-400 text-xs">
                                                    {{ $dayItem['day'] }}
                                                </td>
                                                <td class="py-2.5 px-4 whitespace-nowrap">
                                                    <span class="font-bold text-gray-800 text-xs">{{ $dayItem['formatted_date'] }}</span>
                                                </td>
                                                <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                                        {{ $st }}
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-4">
                                                    @if(!empty($dayItem['remarks']))
                                                        <div class="text-xs font-semibold text-gray-800 bg-amber-50/60 border border-amber-150 px-3 py-1.5 rounded-xl inline-block">
                                                            <span class="text-amber-800 font-bold">&ldquo;{{ $dayItem['remarks'] }}&rdquo;</span>
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-gray-300 italic">&mdash;</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-4">
                                                    @if(!empty($dayItem['substitutions']))
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            @foreach($dayItem['substitutions'] as $subDuty)
                                                                <span class="px-2 py-0.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-150">
                                                                    P{{ $subDuty['period_no'] }}: {{ $subDuty['class_name'] }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-gray-300 italic">&mdash;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-8 text-center text-gray-400 text-sm">
                                                    No attendance history found for this teacher.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="glass-card rounded-2xl p-8 text-center text-gray-400">
                            Please select a teacher to view their detailed attendance and remarks history.
                        </div>
                    @endif
                </div>
            @endif

            {{-- ------------------------------------------------------------ --}}
            {{-- SUB-TAB: WORKLOAD DISTRIBUTION                               --}}
            {{-- ------------------------------------------------------------ --}}
            @if($reportTab === 'workload')
                <div class="glass-card rounded-2xl p-4 sm:p-6 space-y-6">
                    {{-- Header with Monthly toggle --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <h3 class="text-base font-bold text-gray-800">
                                Substitution Workload Ranking &mdash; {{ \Carbon\Carbon::parse($selectedDate)->format('M Y') }}
                            </h3>
                        </div>

                        {{-- Toggle Button --}}
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="$toggle('showMonthlyCount')"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $showMonthlyCount ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $showMonthlyCount ? 'Monthly Breakdown: ON' : 'Monthly Breakdown: OFF' }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Workload Ranking Grid --}}
                    @php
                        $workloadRows = collect($teachers)->map(function($t) use ($dailySubCounts, $monthlySubCounts) {
                            return [
                                'id'      => $t->id,
                                'name'    => $t->name,
                                'email'   => $t->email,
                                'today'   => $dailySubCounts[$t->id] ?? 0,
                                'monthly' => $monthlySubCounts[$t->id] ?? 0,
                            ];
                        })->sortByDesc('today')->sortByDesc('monthly')->values();
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        @foreach($workloadRows as $row)
                            @php
                                $todayL = $row['today'];
                                if ($todayL === 0)      { $cardCls = 'bg-white border-gray-150';     $badgeCls = 'bg-gray-100 text-gray-600';     $dotCls = 'bg-gray-300'; }
                                elseif ($todayL === 1)  { $cardCls = 'bg-amber-50/70 border-amber-200'; $badgeCls = 'bg-amber-100 text-amber-800';   $dotCls = 'bg-amber-500'; }
                                elseif ($todayL === 2)  { $cardCls = 'bg-orange-50/70 border-orange-200'; $badgeCls = 'bg-orange-100 text-orange-800'; $dotCls = 'bg-orange-500'; }
                                else                    { $cardCls = 'bg-red-50/70 border-red-200';       $badgeCls = 'bg-red-100 text-red-800';       $dotCls = 'bg-red-500'; }
                            @endphp
                            <div class="p-3.5 rounded-xl border {{ $cardCls }} flex items-center justify-between gap-3 shadow-sm hover:shadow-md transition-shadow">
                                <div class="min-w-0 flex items-center gap-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $dotCls }} shrink-0"></span>
                                    <div class="truncate">
                                        <div class="text-sm font-bold text-gray-800 truncate" title="{{ $row['name'] }}">{{ $row['name'] }}</div>
                                        <div class="text-xs text-gray-400">Staff Member</div>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $badgeCls }}" title="Substitutions today">
                                        {{ $row['today'] }} today
                                    </span>
                                    @if($showMonthlyCount)
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700" title="Substitutions this month">
                                            {{ $row['monthly'] }}m
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL: CLOSE CLASSROOM                                       --}}
    {{-- ============================================================ --}}
    @if($showCloseClassModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-150 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-150">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Close Classroom for Today</h3>
                            <p class="text-xs text-gray-500">Marks a class as closed and frees its teachers for substitutions</p>
                        </div>
                    </div>
                    <button wire:click="closeCloseClassModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form to Close a Class --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Select Class or Group</label>
                        <select wire:model="closeClassId" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold bg-white focus:ring-2 focus:ring-rose-500 outline-none">
                            <option value="">-- Choose Class or Bulk Group --</option>
                            <optgroup label="Bulk Actions">
                                <option value="school_all">All School Classes (Grades 6 - 10)</option>
                                <option value="college_all">All College Classes (Grades 11 - 12)</option>
                                @php
                                    $uniqueGrades = collect($classesForCurrentShift)->pluck('numeric_value')->filter()->unique()->sort();
                                @endphp
                                @foreach($uniqueGrades as $g)
                                    <option value="grade_all_{{ $g }}">All Grade {{ $g }} Classes</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Individual Classes">
                                @foreach($classesForCurrentShift as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        @error('closeClassId') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Reason / Note (Optional)</label>
                        <input 
                            type="text" 
                            wire:model="closeClassReason" 
                            placeholder="e.g. Field Trip, Sports Event, Exam Duty..."
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-rose-500 outline-none"
                        >
                    </div>

                    <button 
                        type="button" 
                        wire:click="saveCloseClass" 
                        class="w-full py-2.5 bg-rose-600 text-white rounded-xl font-bold text-sm hover:bg-rose-700 shadow-md shadow-rose-200 transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Close Selected Class(es)</span>
                    </button>
                </div>

                {{-- List of Currently Closed Classes for Today --}}
                @if(count($closedClassesList) > 0)
                    <div class="pt-4 border-t border-gray-150 space-y-2">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide">Classes Closed Today ({{ count($closedClassesList) }})</label>
                        <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                            @foreach($closedClassesList as $closed)
                                <div class="flex items-center justify-between p-2.5 bg-rose-50/70 border border-rose-150 rounded-xl text-xs">
                                    <div>
                                        <span class="font-bold text-gray-800">{{ $closed['class_name'] }}</span>
                                        <span class="text-rose-700 ml-1.5">({{ $closed['reason'] }})</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        wire:click="deleteClosedClass({{ $closed['id'] }})"
                                        class="px-2 py-1 bg-white hover:bg-rose-100 text-rose-700 font-bold rounded-lg border border-rose-200 transition-all"
                                        title="Re-open this class"
                                    >
                                        Re-open
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-2 flex justify-end">
                    <button 
                        type="button" 
                        wire:click="closeCloseClassModal" 
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL: MERGE CLASSROOMS                                      --}}
    {{-- ============================================================ --}}
    @if($showMergeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-150 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-150">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Merge Classes for Today</h3>
                            <p class="text-xs text-gray-500">Combines two classes; frees the source class teacher for substitutions</p>
                        </div>
                    </div>
                    <button wire:click="closeMergeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Merge Selection Form --}}
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Source Class (Merged Into Target)</label>
                            <select wire:model="mergeSourceClassId" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-semibold bg-white focus:ring-2 focus:ring-purple-500 outline-none">
                                <option value="">-- Source Class --</option>
                                @foreach($classesForCurrentShift as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                            @error('mergeSourceClassId') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Target Class (Receiving Class)</label>
                            <select wire:model="mergeTargetClassId" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-semibold bg-white focus:ring-2 focus:ring-purple-500 outline-none">
                                <option value="">-- Target Class --</option>
                                @foreach($classesForCurrentShift as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                            @error('mergeTargetClassId') <span class="text-xs text-rose-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="p-3 bg-purple-50 rounded-xl text-purple-900 text-xs border border-purple-150">
                        <span class="font-bold">How it works:</span> Today's timetable periods for the Source Class will be combined with the Target Class. The teacher assigned to the Target Class will teach both groups, freeing the Source Class teacher for substitute duties.
                    </div>

                    <button 
                        type="button" 
                        wire:click="saveMergeClass" 
                        class="w-full py-2.5 bg-purple-600 text-white rounded-xl font-bold text-sm hover:bg-purple-700 shadow-md shadow-purple-200 transition-all flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span>Merge Classes for Today</span>
                    </button>
                </div>

                {{-- List of Currently Merged Classes for Today --}}
                @if(count($mergedClassesList) > 0)
                    <div class="pt-4 border-t border-gray-150 space-y-2">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide">Active Class Merges Today ({{ count($mergedClassesList) }})</label>
                        <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                            @foreach($mergedClassesList as $m)
                                <div class="flex items-center justify-between p-2.5 bg-purple-50/70 border border-purple-150 rounded-xl text-xs">
                                    <div>
                                        <span class="font-bold text-gray-800">{{ $m['source_class_name'] }}</span>
                                        <span class="text-purple-600 mx-1">&rarr;</span>
                                        <span class="font-bold text-purple-900">{{ $m['target_class_name'] }}</span>
                                        <span class="text-gray-400 text-[11px] ml-1">({{ $m['periods_count'] }} periods)</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        wire:click="unmergeClasses({{ $m['source_class_id'] }}, {{ $m['target_class_id'] }})"
                                        class="px-2 py-1 bg-white hover:bg-purple-100 text-purple-700 font-bold rounded-lg border border-purple-200 transition-all"
                                        title="Cancel merge and separate classes"
                                    >
                                        Unmerge
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-2 flex justify-end">
                    <button 
                        type="button" 
                        wire:click="closeMergeModal" 
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- html2pdf for direct client-side PDF download --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    if (typeof html2pdf === 'undefined') {
        document.write('<script src="{{ asset('js/html2pdf.bundle.min.js') }}"><\/script>');
    }
</script>
<script>
    function downloadPdfDirectly(event, url, dateStr) {
        event.preventDefault();
        const btn = event.currentTarget;
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Generating...</span>
        `;
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error("Failed to fetch PDF template");
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const reportContent = doc.getElementById('report-content');
                if (!reportContent) throw new Error("Report content container not found in fetched HTML");
                const tempDiv = document.createElement('div');
                tempDiv.style.position = 'absolute';
                tempDiv.style.left = '-9999px';
                tempDiv.style.top = '-9999px';
                tempDiv.style.width = '900px';
                tempDiv.innerHTML = reportContent.innerHTML;
                document.body.appendChild(tempDiv);
                const opt = {
                    margin:       [10, 10, 10, 10],
                    filename:     'Teacher_Arrangement_' + dateStr + '.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, allowTaint: true },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };
                return html2pdf().set(opt).from(tempDiv).save().then(() => { tempDiv.remove(); });
            })
            .catch(err => {
                console.error(err);
                alert("Error generating PDF. Please try again.");
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
    }
</script>
