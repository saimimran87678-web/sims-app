<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Substitution & Attendance</h1>
            <p class="text-gray-500 text-sm">Manage daily teacher attendance and assign substitutes</p>
        </div>
        <div class="flex gap-3 w-full sm:w-auto">
            <button wire:click="downloadPDF" class="w-full sm:w-auto justify-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium flex items-center gap-2 shadow-sm shadow-blue-200 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download PDF
            </button>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700">{{ session('message') }}</div>
    @endif
    @if($warningMessage)
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl text-yellow-800 font-medium flex gap-2">
            <svg class="w-5 h-5 text-yellow-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{ $warningMessage }}
        </div>
    @endif

    <div class="glass-card rounded-2xl p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6 pb-6 border-b border-gray-100">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
                <input type="date" wire:model.live="selectedDate" class="w-full sm:w-48 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
            </div>
            <div class="text-sm text-gray-500 font-medium sm:mt-5">
                {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
            </div>
        </div>

        <div class="space-y-4">
            @foreach($teachers as $teacher)
                <div class="border border-gray-150 rounded-xl overflow-hidden {{ $teacherStatuses[$teacher->id] !== 'Present' ? 'ring-2 ring-blue-100' : '' }}">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between p-4 bg-gray-50/50 gap-3 border-b border-gray-100">
                        <div class="font-bold text-gray-800 text-base sm:text-lg">{{ $teacher->name }}</div>
                        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 w-full lg:w-auto">
                            <label class="flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors border {{ ($teacherStatuses[$teacher->id] ?? 'Present') === 'Present' ? 'bg-green-100 border-green-200 text-green-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Present" class="hidden">
                                <span class="text-xs sm:text-sm font-medium">Present</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors border {{ ($teacherStatuses[$teacher->id] ?? '') === 'Absent' ? 'bg-red-100 border-red-200 text-red-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Absent" class="hidden">
                                <span class="text-xs sm:text-sm font-medium">Absent</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors border {{ ($teacherStatuses[$teacher->id] ?? '') === 'Leave' ? 'bg-orange-100 border-orange-200 text-orange-850' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Leave" class="hidden">
                                <span class="text-xs sm:text-sm font-medium">Leave</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors border {{ ($teacherStatuses[$teacher->id] ?? '') === 'Official Duty' ? 'bg-blue-100 border-blue-200 text-blue-750' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Official Duty" class="hidden">
                                <span class="text-xs sm:text-sm font-medium">Official Duty</span>
                            </label>
                        </div>
                    </div>

                    @if($teacherStatuses[$teacher->id] !== 'Present')
                        @php $schedule = $this->getTeacherSchedule($teacher->id); @endphp
                        
                        <div class="p-4 bg-white">
                            @if($schedule->isEmpty())
                                <div class="text-gray-500 text-sm text-center py-4">No classes scheduled for this day.</div>
                            @else
                                <div class="space-y-3">
                                    @foreach($schedule as $period)
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-gray-50 rounded-xl border border-gray-100 gap-4">
                                            <div class="w-full md:w-1/3">
                                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Period {{ $period->period_no }}</div>
                                                <div class="font-extrabold text-gray-800 text-base mt-0.5">{{ $period->class_name }}</div>
                                                <div class="text-sm text-gray-600 font-medium">{{ $period->subject_name }}</div>
                                            </div>
                                            
                                            <div class="w-full md:flex-1 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                                                <div class="flex-1">
                                                    @php
                                                        $showAll = $showAllTeachersToggle[$teacher->id][$period->period_no] ?? false;
                                                        $currentSub = $substitutions[$teacher->id][$period->period_no] ?? null;
                                                        $availableList = $showAll ? $teachers : $this->getAvailableTeachersForPeriod($period->period_no, $currentSub, $period->class_id);
                                                    @endphp
                                                    <select 
                                                        wire:model="substitutions.{{ $teacher->id }}.{{ $period->period_no }}"
                                                        wire:change="assignSubstitute({{ $teacher->id }}, {{ $period->period_no }}, {{ $period->class_id }}, {{ $period->subject_id }})"
                                                        class="w-full px-4 py-2 border {{ $showAll ? 'border-yellow-400 focus:ring-yellow-500 focus:border-yellow-500' : 'border-gray-200 focus:ring-blue-500 focus:border-blue-500' }} rounded-xl outline-none bg-white text-sm font-semibold transition-all shadow-sm"
                                                    >
                                                        <option value="">-- Assign Substitute --</option>
                                                        @foreach($availableList as $t)
                                                             @php
                                                                 $assigned = $teacherAssignedSubs[$t->id] ?? [];
                                                                 $badgeParts = [];
                                                                 foreach ($assigned as $subItem) {
                                                                     $badgeParts[] = $subItem['class_name'] . ': P' . $subItem['period_no'];
                                                                 }
                                                                 $subBadge = !empty($badgeParts) ? ' (' . implode(', ', $badgeParts) . ')' : '';
                                                             @endphp
                                                             <option value="{{ $t->id }}">{{ $t->name }}{{ $subBadge }}</option>
                                                         @endforeach
                                                    </select>
                                                    @if($showAll)
                                                        <div class="text-[10px] text-yellow-600 mt-1.5 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                            Showing all teachers. Assignments may cause conflicts.
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="w-full sm:w-auto shrink-0">
                                                    <label class="flex items-center gap-2 cursor-pointer group">
                                                        <input type="checkbox" wire:model.live="showAllTeachersToggle.{{ $teacher->id }}.{{ $period->period_no }}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                        <span class="text-xs font-semibold text-gray-500 group-hover:text-gray-700 transition-colors">Show All (Override)</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
