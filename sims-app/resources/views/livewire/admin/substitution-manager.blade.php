<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Substitution & Attendance</h1>
            <p class="text-gray-500">Manage daily teacher attendance and assign substitutes</p>
        </div>
        <div class="flex gap-3">
            <button wire:click="downloadPDF" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium flex items-center gap-2">
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

    <div class="glass-card rounded-2xl p-6">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
                <input type="date" wire:model.live="selectedDate" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none w-48">
            </div>
            <div class="text-sm text-gray-500 mt-5">
                {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
            </div>
        </div>

        <div class="space-y-4">
            @foreach($teachers as $teacher)
                <div class="border border-gray-100 rounded-xl overflow-hidden {{ $teacherStatuses[$teacher->id] !== 'Present' ? 'ring-2 ring-blue-100' : '' }}">
                    <div class="flex items-center justify-between p-4 bg-gray-50/50">
                        <div class="font-bold text-gray-800">{{ $teacher->name }}</div>
                        <div class="flex gap-2">
                            <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors {{ ($teacherStatuses[$teacher->id] ?? 'Present') === 'Present' ? 'bg-green-100 text-green-700' : 'hover:bg-gray-100' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Present" class="hidden">
                                <span class="text-sm font-medium">Present</span>
                            </label>
                            <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors {{ ($teacherStatuses[$teacher->id] ?? '') === 'Absent' ? 'bg-red-100 text-red-700' : 'hover:bg-gray-100' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Absent" class="hidden">
                                <span class="text-sm font-medium">Absent</span>
                            </label>
                            <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors {{ ($teacherStatuses[$teacher->id] ?? '') === 'Leave' ? 'bg-orange-100 text-orange-700' : 'hover:bg-gray-100' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Leave" class="hidden">
                                <span class="text-sm font-medium">Leave</span>
                            </label>
                            <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg cursor-pointer transition-colors {{ ($teacherStatuses[$teacher->id] ?? '') === 'Official Duty' ? 'bg-blue-100 text-blue-700' : 'hover:bg-gray-100' }}">
                                <input type="radio" wire:model.live="teacherStatuses.{{ $teacher->id }}" value="Official Duty" class="hidden">
                                <span class="text-sm font-medium">Official Duty</span>
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
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                            <div class="w-1/3">
                                                <div class="text-xs font-bold text-gray-500 uppercase">Period {{ $period->period_no }}</div>
                                                <div class="font-medium text-gray-800">{{ $period->class_name }}</div>
                                                <div class="text-sm text-gray-600">{{ $period->subject_name }}</div>
                                            </div>
                                            
                                            <div class="flex-1 flex gap-4 items-start">
                                                <div class="flex-1">
                                                    @php
                                                        $showAll = $showAllTeachersToggle[$teacher->id][$period->period_no] ?? false;
                                                        $currentSub = $substitutions[$teacher->id][$period->period_no] ?? null;
                                                        $availableList = $showAll ? $teachers : $this->getAvailableTeachersForPeriod($period->period_no, $currentSub, $period->class_id);
                                                    @endphp
                                                    <select 
                                                        wire:model="substitutions.{{ $teacher->id }}.{{ $period->period_no }}"
                                                        wire:change="assignSubstitute({{ $teacher->id }}, {{ $period->period_no }}, {{ $period->class_id }}, {{ $period->subject_id }})"
                                                        class="w-full px-4 py-2 border {{ $showAll ? 'border-yellow-300 focus:ring-yellow-500' : 'border-gray-200 focus:ring-blue-500' }} rounded-xl outline-none"
                                                    >
                                                        <option value="">-- Assign Substitute --</option>
                                                        @foreach($availableList as $t)
                                                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if($showAll)
                                                        <div class="text-[10px] text-yellow-600 mt-1 flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                            Showing all teachers. Assignments may cause conflicts.
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="w-40 pt-2">
                                                    <label class="flex items-center gap-2 cursor-pointer group">
                                                        <input type="checkbox" wire:model.live="showAllTeachersToggle.{{ $teacher->id }}.{{ $period->period_no }}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                        <span class="text-xs font-medium text-gray-500 group-hover:text-gray-700">Show All (Override)</span>
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
