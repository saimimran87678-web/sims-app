<div class="h-full flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200 shrink-0">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Teacher Attendance</h1>
            <p class="text-sm text-gray-500">Record attendance and manage substitutions</p>
        </div>
        <div class="flex items-center gap-4">
            <input type="date" 
                   wire:model.live="date" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            
            <button wire:click="saveAttendance" 
                    class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Save Attendance
            </button>
            
            <a href="{{ route('admin.teacher-attendance.pdf') }}?date={{ $date }}" 
               target="_blank"
               class="px-4 py-2 text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download PDF
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="px-6 py-3 bg-green-50 text-green-700 border-b border-green-200 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="px-6 py-3 bg-red-50 text-red-700 border-b border-red-200 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Content -->
    <div class="flex-1 overflow-auto p-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-900 border-b border-gray-200 font-medium uppercase tracking-wider text-xs">
                    <tr>
                        <th class="px-6 py-4">Teacher Name</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Remarks</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($teachers as $teacher)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            {{ $teacher->name }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="present" wire:model="attendanceData.{{ $teacher->id }}" class="text-green-600 focus:ring-green-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'present' ? 'font-bold text-green-700' : '' }}">Present</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="leave" wire:model="attendanceData.{{ $teacher->id }}" class="text-orange-600 focus:ring-orange-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'leave' ? 'font-bold text-orange-700' : '' }}">Leave</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="official_duty" wire:model="attendanceData.{{ $teacher->id }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'official_duty' ? 'font-bold text-blue-700' : '' }}">Official Duty</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="late" wire:model="attendanceData.{{ $teacher->id }}" class="text-yellow-600 focus:ring-yellow-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'late' ? 'font-bold text-yellow-700' : '' }}">Late</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="absent" wire:model="attendanceData.{{ $teacher->id }}" class="text-red-600 focus:ring-red-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'absent' ? 'font-bold text-red-700' : '' }}">Absent</span>
                                </label>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <input type="text" 
                                   wire:model="remarksData.{{ $teacher->id }}"
                                   placeholder="Add remarks..." 
                                   class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white">
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if(in_array($attendanceData[$teacher->id], ['absent', 'official_duty', 'late', 'leave']))
                                <button wire:click="openSubstitutionModal({{ $teacher->id }})" 
                                        class="px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-md transition-colors">
                                    Manage Substitutions
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Substitution Modal -->
    @if($showSubstitutionModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="$wire.closeSubstitutionModal()">
         
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Manage Substitutions</h3>
                    <p class="text-sm text-gray-500">Assign substitutes for {{ $selectedTeacherName }}</p>
                </div>
                <button wire:click="closeSubstitutionModal" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6">
                @if(empty($absentTeacherSchedule))
                    <div class="text-center py-8 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-3 text-gray-300"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <p>No classes scheduled for this teacher on {{ \Carbon\Carbon::parse($date)->format('l') }}.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($absentTeacherSchedule as $timetable)
                        <div class="flex items-center gap-4 p-3 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="w-12 h-12 flex flex-col items-center justify-center bg-blue-50 text-blue-700 rounded-lg shrink-0">
                                <span class="text-xs font-bold uppercase">Period</span>
                                <span class="text-lg font-bold">
                                    {{ $periodLabels[$timetable->schedule_template_id . '_' . $timetable->period_no] ?? 'PERIOD ' . $timetable->period_no }}
                                </span>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 truncate">
                                    {{ $timetable->class->name ?? 'N/A' }} 
                                    <span class="text-gray-400 mx-1">•</span> 
                                    {{ $timetable->subject->name ?? 'N/A' }}
                                    @if($timetable->subject_id_2)
                                        / {{ $timetable->subject2->name ?? 'N/A' }}
                                    @endif
                                </h4>
                                <p class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($timetable->start_time)->format('h:i A') }} - 
                                    {{ \Carbon\Carbon::parse($timetable->end_time)->format('h:i A') }}
                                </p>
                            </div>
                            
                            <div class="w-64">
                                <select wire:model="substitutionData.{{ $timetable->id }}" 
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- No Substitute --</option>
                                    @if(isset($availableTeachers[$timetable->id]))
                                        @foreach($availableTeachers[$timetable->id] as $availTeacher)
                                            <option value="{{ $availTeacher['id'] }}">{{ $availTeacher['name'] }}</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No free teachers found</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button wire:click="closeSubstitutionModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button wire:click="saveSubstitutions" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Save Substitutions
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
