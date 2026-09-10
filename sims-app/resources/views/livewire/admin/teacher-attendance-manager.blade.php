<div class="h-full flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200 shrink-0">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Teacher Attendance</h1>
            <p class="text-sm text-gray-500">Record attendance and manage substitutions</p>
        </div>
        <div class="flex items-center gap-4">
            <input type="date" 
                   wire:model="date" 
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            
            <button wire:click="updatedDate" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-white bg-indigo-500 rounded-lg hover:bg-indigo-600 transition-colors flex items-center gap-2">
                <span>Go</span>
            </button>


            
            <button wire:click="saveAttendance" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Save Attendance
            </button>
            
            <button wire:click="downloadPdf" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download PDF
            </button>
            
            <button wire:click="openMergeModal" 
                    class="px-4 py-2 text-purple-700 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>
                Merge Classes
            </button>

            <button wire:click="openCloseClassModal" 
                    class="px-4 py-2 text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
                Close Class
            </button>
        </div>
    </div>

    @script
    <script>
        $wire.on('open-pdf', (event) => {
            window.open(event.url, '_blank');
        });
    </script>
    @endscript

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

    <!-- Tabs -->
    <div class="px-6 pt-4 flex gap-4 border-b border-gray-200">
        <button wire:click="$set('activeTab', 'attendance')" 
                class="pb-2 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'attendance' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Attendance
        </button>
        <button wire:click="$set('activeTab', 'arrangement')" 
                class="pb-2 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'arrangement' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Arrangement
        </button>
        <button wire:click="$set('activeTab', 'report')" 
                class="pb-2 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'report' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Report
        </button>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-6">
        
        @if($activeTab === 'attendance')
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
                    <tr class="hover:bg-gray-50/50" wire:key="teacher-{{ $teacher->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            {{ $teacher->name }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="present" wire:model.live="attendanceData.{{ $teacher->id }}" class="text-green-600 focus:ring-green-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'present' ? 'font-bold text-green-700' : '' }}">Present</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="leave" wire:model.live="attendanceData.{{ $teacher->id }}" class="text-orange-600 focus:ring-orange-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'leave' ? 'font-bold text-orange-700' : '' }}">Leave</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="official_duty" wire:model.live="attendanceData.{{ $teacher->id }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'official_duty' ? 'font-bold text-blue-700' : '' }}">Official Duty</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="short_leave" wire:model.live="attendanceData.{{ $teacher->id }}" class="text-yellow-600 focus:ring-yellow-500">
                                    <span class="text-sm {{ $attendanceData[$teacher->id] == 'short_leave' ? 'font-bold text-yellow-700' : '' }}">Short Leave</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="absent" wire:model.live="attendanceData.{{ $teacher->id }}" class="text-red-600 focus:ring-red-500">
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
                        <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                            @if(in_array($attendanceData[$teacher->id], ['absent', 'leave', 'official_duty', 'short_leave']) && in_array($teacher->id, $savedTeacherIds))
                                <button wire:click="openSubstitutionModal({{ $teacher->id }})" 
                                        class="text-xs px-2 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded hover:bg-purple-100 transition-colors">
                                    Manage Substitute
                                </button>
                            @endif
                            @if(in_array($attendanceData[$teacher->id], ['absent', 'leave', 'official_duty', 'short_leave']) && in_array($teacher->id, $savedTeacherIds))
                                <span class="text-xs text-gray-500 italic">Marked as {{ str_replace('_', ' ', $attendanceData[$teacher->id]) }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button wire:click="$set('showNoteModal', true)" 
                    class="px-4 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Add Daily Note
            </button>
        </div>
        @endif

        @if($activeTab === 'arrangement')
        <div class="space-y-6">
            @forelse($this->arrangements as $arrangement)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $arrangement['teacher_name'] }}</h3>
                        <div class="text-sm text-gray-500 mt-1">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $arrangement['status'] == 'absent' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $arrangement['status'] == 'leave' ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $arrangement['status'] == 'short_leave' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $arrangement['status'] == 'official_duty' ? 'bg-blue-100 text-blue-700' : '' }}
                            ">
                                {{ strtoupper(str_replace('_', ' ', $arrangement['status'])) }}
                            </span>
                            @if($arrangement['remarks'])
                                <span class="ml-2 text-gray-400">&bull; {{ $arrangement['remarks'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="p-0">
                    @if(count($arrangement['classes']) > 0)
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50/50 text-gray-500 font-medium text-xs uppercase">
                            <tr>
                                <th class="px-6 py-3 w-32">Period</th>
                                <th class="px-6 py-3">Class & Subject</th>
                                <th class="px-6 py-3">Substitute Teacher</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($arrangement['classes'] as $class)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-900">
                                    {{ $periodLabels[$class['period_no']] ?? 'Period ' . $class['period_no'] }}
                                </td>
                                <td class="px-6 py-3 text-gray-600">
                                    {{ $class['class_name'] }} - <span class="text-gray-400">{{ $class['subject_name'] }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    @if($class['sub_teacher_name'])
                                        <div class="flex items-center gap-2 text-green-700 font-medium">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            {{ $class['sub_teacher_name'] }}
                                        </div>
                                    @else
                                        <span class="text-red-500 text-xs italic flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Not Assigned
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="p-6 text-center text-gray-400 text-sm italic">
                        No classes scheduled for today.
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <div class="bg-gray-50 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">All Teachers Present</h3>
                <p class="text-gray-500 mt-1">No arrangements needed at the moment.</p>
            </div>
            @endforelse
        </div>
        @endif

        @if($activeTab === 'report')
        <div class="space-y-6">
            <!-- Date Filter -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" wire:model="reportFromDate" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" wire:model="reportToDate" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button wire:click="generateReport" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    Generate Report
                </button>
            </div>

            <!-- Report Table -->
            @if(!empty($reportData))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-900 border-b border-gray-200 font-medium uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4">Teacher Name</th>
                            <th class="px-6 py-4 text-center">No. of Arrangements</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($reportData as $data)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $data['name'] }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold {{ $data['count'] > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                                {{ $data['count'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @elseif($reportData !== null) <!-- Only show if report has been generated (reportData initialized as empty array, but we might want a flag or just check if empty after action) -->
                 <!-- Actually reportData is initialized as [] in component. So it will be empty initially. 
                      Maybe we can check if we have a flag $reportGenerated or just show "No data" if specific condition met.
                      For now, let's just show nothing or a placeholder if empty AND generated. 
                      Let's stick to showing nothing if empty for cleaner UI on load, or a generic placeholder. -->
            @endif
        </div>
        @endif
    </div>


    {{-- Debug Output --}}
    
    <div class="fixed bottom-0 right-0 bg-black text-white p-4 z-50 opacity-75">
        Modal State: {{ $showSubstitutionModal ? 'TRUE' : 'FALSE' }}<br>
        Teacher ID: {{ $manageForTeacherId }}<br>
        Data Count: {{ count($substitutionData) }}
    </div> 
    

    {{-- Substitution Modal --}}
    @if($showSubstitutionModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="$wire.set('showSubstitutionModal', false)">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[85vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Manage Substitution: {{ $manageForTeacherName }}</h3>
                <button wire:click="$set('showSubstitutionModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                @if(count($substitutionData) > 0)
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 font-medium border-b border-gray-200">
                        <tr>
                            <th class="p-3">Period</th>
                            <th class="p-3">Class</th>
                            <th class="p-3">Subject</th>
                            <th class="p-3">Substitute Teacher</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($substitutionData as $data)
                            <tr>
                                <td class="p-3">
                                    <span class="font-medium">{{ $periodLabels[$data['period_no']] ?? 'Period ' . $data['period_no'] }}</span>
                                </td>
                                <td class="p-3">{{ $data['class_names'] }}</td>
                                <td class="p-3">{{ $data['subject_names'] }}</td>
                                <td class="p-3">
                                    <select wire:model="substitutions.period_{{ $data['period_no'] }}" 
                                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- No Substitute --</option>
                                        @foreach($data['free_teachers'] as $ft)
                                        <option value="{{ $ft->id }}">
                                            {{ $ft->name }} ({{ $dailySubstitutionCounts[$ft->id] ?? 0 }})
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div class="text-center py-8 text-gray-500">
                        No classes found for this teacher today.
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button wire:click="$set('showSubstitutionModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button wire:click="saveSubstitutions" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Save Substitutions
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Merge Classes Modal --}}
    @if($showMergeModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="$wire.set('showMergeModal', false)">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                <h3 class="font-bold text-gray-900">Merge Classes for Today</h3>
                <button wire:click="$set('showMergeModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4 overflow-y-auto">
                {{-- Period selection removed for Whole Day merge --}}
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source Class (Becomes Free)</label>
                        <select wire:model="mergeSourceClassId" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select Class --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-blue-600 mt-1 font-medium">Teacher of this class will become available.</p>
                        @error('mergeSourceClassId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Class (Conducts Merge)</label>
                        <select wire:model="mergeTargetClassId" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select Class --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-purple-600 mt-1 font-medium">This teacher will conduct both classes.</p>
                        @error('mergeTargetClassId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="bg-blue-50 p-3 rounded text-sm text-blue-700 mb-4">
                    <p><strong>Note:</strong> Merging applies only for today ({{ \Carbon\Carbon::parse($mergeDate)->format('d M Y') }}).</p>
                </div>
                
                <div class="flex justify-end border-b border-gray-100 pb-4">
                    <button wire:click="saveMerge" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Save Merge
                    </button>
                </div>

                {{-- List of Merged Classes --}}
                <div class="mt-4 flex flex-col min-h-0 flex-1">
                    <h4 class="text-sm font-bold text-gray-900 mb-3">Currently Merged Classes ({{ \Carbon\Carbon::parse($mergeDate)->format('d M Y') }})</h4>
                    <div class="space-y-2 pr-2">
                        @forelse($mergedClassesList as $merge)
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-3 bg-gray-50 rounded-lg border border-gray-200 gap-2">
                            <div>
                                <div class="text-sm">
                                    <span class="font-bold text-gray-900">{{ $merge['source_class_name'] }}</span>
                                    <span class="text-gray-500 mx-1">merged into</span>
                                    <span class="font-bold text-gray-900">{{ $merge['target_class_name'] }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $merge['count'] }} period(s) merged</div>
                            </div>
                            <button wire:click="unmergeClasses({{ $merge['source_class_id'] }}, {{ $merge['target_class_id'] }})" class="text-xs px-2 py-1 text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded transition-colors whitespace-nowrap">
                                Unmerge
                            </button>
                        </div>
                        @empty
                        <div class="text-center py-4 text-sm text-gray-500 italic bg-gray-50 rounded-lg border border-gray-100">
                            No classes merged for this date.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 shrink-0">
                <button wire:click="$set('showMergeModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Done
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Close Class Modal --}}
    @if($showCloseClassModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="$wire.set('showCloseClassModal', false)">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Close Class for Today</h3>
                <button wire:click="$set('showCloseClassModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-6">
                
                {{-- Form --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Class to Close</label>
                        <select wire:model="closeClassId" class="w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                            <option value="">-- Select Class --</option>
                            <optgroup label="Bulk Actions">
                                <option value="school_all" class="font-bold text-red-600">-- Close School (6-10) --</option>
                                <option value="college_all" class="font-bold text-red-600">-- Close College (11-12) --</option>
                            </optgroup>
                            @foreach($classes->groupBy('numeric_value') as $grade => $gradeClasses)
                                <optgroup label="Class {{ $grade }}">
                                    <option value="grade_all_{{ $grade }}" class="font-bold text-red-600">Class {{ $grade }} (All Sections)</option>
                                    @foreach($gradeClasses as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-xs text-red-600 mt-1 font-medium">Teachers of this class with periods today will become available.</p>
                        @error('closeClassId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                        <input type="text" wire:model="closeClassReason" class="w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="e.g. Field Trip">
                    </div>

                    <div class="flex justify-end">
                         <button wire:click="saveCloseClass" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Close Class
                        </button>
                    </div>
                </div>

                {{-- List of Closed Classes --}}
                <div class="border-t border-gray-100 pt-4 flex flex-col min-h-0 flex-1">
                    <h4 class="text-sm font-bold text-gray-900 mb-3">Currently Closed Classes ({{ \Carbon\Carbon::parse($date)->format('d M Y') }})</h4>
                    <div class="space-y-2 overflow-y-auto pr-2" style="max-height: 350px;">
                        @foreach($closedClasses as $closed)
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border border-red-100">
                            <div>
                                <span class="font-bold text-gray-900">{{ $closed->class->name }}</span>
                                @if($closed->reason)
                                    <span class="text-sm text-gray-500"> - {{ $closed->reason }}</span>
                                @endif
                            </div>
                            <button wire:click="deleteClosedClass({{ $closed->id }})" class="text-xs text-red-600 hover:text-red-800 underline">
                                Re-open
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button wire:click="$set('showCloseClassModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Done
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Daily Note Modal --}}
    @if($showNoteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="$wire.set('showNoteModal', false)">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-900">Add Daily Note</h3>
                <button wire:click="$set('showNoteModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6">
                <p class="text-sm text-gray-500 mb-3">This note will be saved for this date <span class="font-bold">({{ \Carbon\Carbon::parse($date)->format('d M Y') }})</span> and appear at the bottom of the PDF report.</p>
                <textarea wire:model="dailyNote" 
                          rows="5" 
                          class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Enter specific notes or instructions for today..."></textarea>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <button wire:click="$set('showNoteModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button wire:click="saveNote" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Save Note
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
