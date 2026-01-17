<div class="space-y-6">
    {{-- Header Slot --}}
    @section('header')
        <div class="flex items-center gap-4 w-full">
            {{-- Back Button (Icon Only) --}}
            @php
                $backRoute = request()->is('teacher/*') ? route('teacher.shared.exams') : route('admin.exams');
            @endphp
            <a href="{{ $backRoute }}" class="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Back to Exams">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Datesheet for') }} <span class="text-indigo-600">{{ $exam->name }}</span>
            </h2>
        </div>
    @endsection

    {{-- Controls & Stats --}}
    <div class="bg-white overflow-hidden shadow-md sm:rounded-xl p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-center gap-4 border border-gray-100">
        
        {{-- View Mode Toggle --}}
        <div class="flex bg-gray-100/80 p-1 rounded-xl shrink-0 shadow-inner">
            <button 
                wire:click="toggleMode('schedule')"
                class="px-5 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ $viewMode === 'schedule' ? 'bg-white text-indigo-700 shadow-md ring-1 ring-indigo-100' : 'text-gray-500 hover:text-gray-800 hover:bg-white/50' }}"
            >
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Schedule Editor
                </span>
            </button>
            <button 
                wire:click="toggleMode('marks')"
                class="px-5 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 {{ $viewMode === 'marks' ? 'bg-white text-indigo-700 shadow-md ring-1 ring-indigo-100' : 'text-gray-500 hover:text-gray-800 hover:bg-white/50' }}"
            >
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Marks Configuration
                </span>
            </button>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3 justify-center lg:justify-end">
            @if($viewMode === 'schedule')
                {{-- Date Inputs Toolbar --}}
                <div class="flex flex-row items-end gap-3 bg-gradient-to-br from-gray-50 to-slate-100 p-3 rounded-xl border border-gray-200/80 shadow-sm">
                    <div class="flex flex-col">
                        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1 tracking-wider">Start Date</label>
                        <input 
                            type="date" 
                            wire:model="startDate" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-lg shadow-sm text-sm h-10 w-32 sm:w-36 px-3 bg-white"
                        >
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-[10px] uppercase font-bold text-gray-500 mb-1 tracking-wider">End Date</label>
                        <input 
                            type="date" 
                            wire:model="endDate" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-lg shadow-sm text-sm h-10 w-32 sm:w-36 px-3 bg-white"
                        >
                    </div>
                    <button 
                        wire:click="addDateRange" 
                        class="h-10 inline-flex items-center px-5 rounded-lg font-bold text-xs uppercase tracking-widest shadow-md hover:shadow-lg transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-150 whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400"
                        style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Add
                    </button>
                </div>
            @endif

            {{-- Print Button (Standalone) --}}
            @php
                $printRoute = route('admin.datesheet.print', $exam->id); // Print always uses admin route (it's a print-only view)
            @endphp
            <a 
                href="{{ $printRoute }}?download=true" 
                target="_blank" 
                class="h-10 inline-flex items-center px-5 rounded-lg font-bold text-xs uppercase tracking-widest shadow-md hover:shadow-lg transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-150 whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-400"
                style="background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); color: white;"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print
            </a>

            @if($viewMode === 'marks')
                <button 
                    wire:click="saveMarks" 
                    class="h-10 inline-flex items-center px-5 rounded-lg font-bold text-xs uppercase tracking-widest shadow-md hover:shadow-lg transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-150 whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-400"
                    style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    Save Changes
                </button>
            @endif
        </div>
    </div>

    @if(session()->has('message'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Class Filter Section --}}
    @if($viewMode === 'schedule')
    <div class="bg-white p-4 rounded-lg border border-indigo-100 mb-6" x-data="{ expanded: false }">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-indigo-900 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Select Classes to Include:
            </h3>
            <button @click="expanded = !expanded" class="text-xs text-indigo-600 hover:text-indigo-800 underline">
                <span x-show="!expanded">Show Sections</span>
                <span x-show="expanded">Hide Sections</span>
            </button>
        </div>
        
        <div class="flex flex-wrap gap-6">
            @foreach($groupedClasses as $grade => $classes)
                @php
                    $gradeClassIds = collect($classes)->pluck('id')->toArray();
                    $selectedCount = count(array_intersect($gradeClassIds, $visibleClassIds));
                    $allSelected = $selectedCount === count($gradeClassIds);
                    $someSelected = $selectedCount > 0 && !$allSelected;
                @endphp
                
                <div class="flex flex-col gap-2 p-2 bg-indigo-50 rounded-lg transition-all" :class="{'bg-white shadow-md z-10': expanded}">
                    {{-- Parent Checkbox --}}
                    <label class="inline-flex items-center space-x-2 cursor-pointer">
                        <input 
                            type="checkbox" 
                            wire:click="toggleGrade('{{ $grade }}')" 
                            {{ $allSelected ? 'checked' : '' }}
                            class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 {{ $someSelected ? 'text-indigo-400 opacity-75' : '' }}"
                        >
                        <span class="text-sm font-bold text-indigo-700 w-24">{{ $grade }}th Grade</span>
                    </label>

                    {{-- Child Checkboxes --}}
                    <div class="flex flex-col gap-1 pl-6 border-l-2 border-indigo-200" x-show="expanded">
                        @foreach($classes as $class)
                            @php $classId = is_array($class) ? $class['id'] : $class->id; @endphp
                            <label class="inline-flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="visibleClassIds" value="{{ $classId }}" class="rounded border-gray-300 text-sm w-3 h-3 text-indigo-500">
                                <span class="text-xs text-gray-600">{{ $class['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Main Content Area --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
        
        {{-- SCHEDULE MATRIX --}}
        @if($viewMode === 'schedule')
        <div class="overflow-x-auto max-h-[75vh]">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100 sticky top-0 z-30">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider sticky left-0 bg-gray-100 z-40 w-32 border-b border-gray-200">
                            Date
                        </th>
                         <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider bg-gray-100 border-b border-gray-200 w-24">
                            Day
                        </th>
                        @foreach($groupedClasses as $grade => $classes)
                            @php
                                $gradeClassIds = collect($classes)->pluck('id')->toArray();
                                $visibleInGrade = array_intersect($gradeClassIds, $visibleClassIds);
                                // Merge logic: If ALL classes in this grade are selected, show 1 column
                                $isMerged = (count($visibleInGrade) === count($gradeClassIds));
                            @endphp

                            @if($isMerged)
                                {{-- Merged Header --}}
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-l border-gray-200 border-b border-gray-200 min-w-[150px]">
                                    {{ $grade }}th
                                </th>
                            @else
                                {{-- Individual Headers --}}
                                @foreach($classes as $class)
                                    @php $classId = is_array($class) ? $class['id'] : $class->id; @endphp
                                    @if(in_array($classId, $visibleClassIds))
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-l border-gray-200 border-b border-gray-200">
                                            {{ $class['name'] }}
                                        </th>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                        {{-- Action Column Header --}}
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-l border-gray-200 border-b border-gray-200 w-24">
                            Action
                        </th>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="sticky left-0 bg-gray-50 z-20 border-b border-gray-200"></td>
                        <td class="border-b border-gray-200 bg-gray-50"></td>
                        @foreach($groupedClasses as $grade => $classes)
                            @php
                                $gradeClassIds = collect($classes)->pluck('id')->toArray();
                                $visibleInGrade = array_intersect($gradeClassIds, $visibleClassIds);
                                $isMerged = (count($visibleInGrade) === count($gradeClassIds));
                            @endphp

                            @if($isMerged)
                                <th class="px-3 py-1 text-center text-[10px] font-semibold text-gray-500 border-l border-gray-200 border-b border-gray-200">
                                    {{ count($visibleInGrade) === 1 ? ($classes[0]['name'] ?? $classes[0]->name) : 'All Sections' }}
                                </th>
                            @else
                                @foreach($classes as $class)
                                    @php $classId = is_array($class) ? $class['id'] : $class->id; @endphp
                                    @if(in_array($classId, $visibleClassIds))
                                        <th class="px-3 py-1 text-center text-[10px] font-semibold text-gray-500 border-l border-gray-200 border-b border-gray-200">
                                            {{ $class['name'] }}
                                        </th>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                        <td class="border-l border-b border-gray-200 bg-gray-50"></td>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($dates as $date)
                        <tr class="hover:bg-gray-50 transition-colors duration-150 group">
                            {{-- Date Column --}}
                            <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-20 border-r border-gray-200 group-hover:bg-gray-50">
                                <span class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}</span>
                            </td>
                            {{-- Day Column --}}
                            <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-500 group-hover:bg-gray-50 border-r border-gray-200">
                                {{ \Carbon\Carbon::parse($date)->format('l') }}
                            </td>
                            
                            {{-- Schedule Cells --}}
                            @foreach($groupedClasses as $grade => $classes)
                                @php
                                    $gradeClassIds = collect($classes)->pluck('id')->toArray();
                                    $visibleInGrade = array_unique(array_intersect($gradeClassIds, $visibleClassIds));
                                    $isMerged = (count($visibleInGrade) === count($gradeClassIds));
                                @endphp

                                @if($isMerged)
                                    {{-- Merged Cell Logic with Inline Editor --}}
                                    @php
                                        // Collect unique subjects for all visible classes in this grade
                                        $subjects = [];
                                        foreach($visibleInGrade as $cid) {
                                            $s = $scheduleMatrix[$date][$cid] ?? null;
                                            if ($s && $s !== '-') $subjects[] = $s; 
                                        }
                                        $subjects = array_unique($subjects);
                                        $displaySubject = empty($subjects) ? null : implode(' / ', $subjects);
                                        $isHoliday = in_array('Holiday', $subjects);
                                        $isEmpty = empty($displaySubject) || $displaySubject === '-';
                                        
                                        // Get assigned subjects for filtering dropdown
                                        $assignedSubjects = [];
                                        foreach($visibleInGrade as $cid) {
                                            $current = $scheduleMatrix[$date][$cid] ?? '-';
                                            if ($current && $current !== '-' && $current !== 'Holiday') {
                                                foreach(explode(',', $current) as $subj) {
                                                    $assignedSubjects[] = trim($subj);
                                                }
                                            }
                                        }
                                        $assignedSubjects = array_unique($assignedSubjects);
                                        
                                        // Available subjects (not already assigned)
                                        $availableForGrade = array_diff($gradeSubjects[$grade] ?? [], $assignedSubjects);
                                        
                                        $isEditing = ($inlineEditDate === $date && $inlineEditGrade == $grade);
                                    @endphp
                                    <td 
                                        class="px-3 py-2 text-center border-l border-gray-100 transition-all duration-200 relative min-w-[140px]
                                            {{ $isHoliday ? 'bg-amber-100' : '' }}
                                            {{ $isEmpty && !$isHoliday ? 'hover:bg-blue-50 cursor-pointer' : 'hover:bg-gray-50' }}
                                        "
                                        @if(!$isEditing)
                                            wire:click="openInlineEditor('{{ $date }}', '{{ $grade }}')"
                                        @endif
                                    >
                                        {{-- Display Mode --}}
                                        @if(!$isEditing)
                                            @if($isHoliday)
                                                <span class="text-amber-600 font-medium text-xs uppercase tracking-wide">Holiday</span>
                                            @elseif($isEmpty)
                                                <span class="text-gray-300 text-lg">—</span>
                                            @else
                                                <div class="flex flex-wrap gap-1 justify-center">
                                                    @foreach($assignedSubjects as $subj)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium">
                                                            {{ $subj }}
                                                            <button 
                                                                wire:click.stop="removeSubjectFromGrade('{{ $date }}', '{{ $grade }}', '{{ $subj }}')"
                                                                class="ml-0.5 text-indigo-400 hover:text-red-500"
                                                            >&times;</button>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                @else
                                    {{-- Individual Cells (when grades are partially selected) --}}
                                    @foreach($classes as $class)
                                        @php $classId = is_array($class) ? $class['id'] : $class->id; @endphp
                                        @if(in_array($classId, $visibleClassIds))
                                            @php
                                                $subject = $scheduleMatrix[$date][$classId] ?? null;
                                                $isHoliday = $subject === 'Holiday';
                                                $isEmpty = empty($subject) || $subject === '-';
                                                
                                                // Get assigned subjects
                                                $assignedSubjects = [];
                                                if ($subject && $subject !== '-' && $subject !== 'Holiday') {
                                                    $assignedSubjects = array_map('trim', explode(',', $subject));
                                                }
                                                
                                                // Available subjects for this grade
                                                $availableForGrade = array_diff($gradeSubjects[$grade] ?? [], $assignedSubjects);
                                                
                                                $isEditing = ($inlineEditDate === $date && $inlineEditGrade == $grade);
                                            @endphp
                                            <td 
                                                class="px-3 py-2 text-center border-l border-gray-100 transition-all duration-200 relative min-w-[120px]
                                                    {{ $isHoliday ? 'bg-amber-100' : '' }}
                                                    {{ $isEmpty && !$isHoliday ? 'hover:bg-blue-50 cursor-pointer' : 'hover:bg-gray-50' }}
                                                "
                                                @if(!$isEditing)
                                                    wire:click="openInlineEditor('{{ $date }}', '{{ $grade }}')"
                                                @endif
                                            >
                                                @if($isHoliday)
                                                    <span class="text-amber-600 font-medium text-xs uppercase tracking-wide">Holiday</span>
                                                @elseif($isEmpty)
                                                    <span class="text-gray-300 text-lg">—</span>
                                                @else
                                                    <div class="flex flex-wrap gap-1 justify-center">
                                                        @foreach($assignedSubjects as $subj)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-xs font-medium">
                                                                {{ $subj }}
                                                                <button 
                                                                    wire:click.stop="removeSubjectFromGrade('{{ $date }}', '{{ $grade }}', '{{ $subj }}')"
                                                                    class="ml-0.5 text-indigo-400 hover:text-red-500"
                                                                >&times;</button>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach

                            {{-- Action Column --}}
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium border-l border-gray-200">
                                <button 
                                    wire:click="deleteDate('{{ $date }}')"
                                    wire:confirm="Are you sure you want to delete this date?"
                                    class="text-red-400 hover:text-red-600 transition-colors p-1 rounded hover:bg-red-50" 
                                    title="Delete Row"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100" class="px-6 py-12 text-center text-gray-400 bg-gray-50">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <p class="text-lg font-medium">No dates added yet.</p>
                                    <p class="text-sm">Add a date above to start building the schedule.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- SUBJECT SELECTOR MODAL (Centered Popup) --}}
        @if($inlineEditDate && $inlineEditGrade)
            @php
                // Get class IDs that belong to this grade
                $gradeClassIds = collect($groupedClasses[$inlineEditGrade] ?? [])->pluck('id')->toArray();
                
                // SMART SELECTION: Get subjects already scheduled on OTHER dates for this grade
                // This means if "Math" was scheduled on Dec 21 for Class 10, it won't appear 
                // when scheduling Dec 22 for Class 10 (but it CAN appear for other grades)
                $scheduledOnOtherDates = [];
                foreach($dates as $d) {
                    if ($d === $inlineEditDate) continue; // Skip current date
                    
                    foreach($gradeClassIds as $cid) {
                        $subj = $scheduleMatrix[$d][$cid] ?? null;
                        if ($subj && $subj !== '-' && $subj !== 'Holiday') {
                            foreach(explode(', ', $subj) as $s) {
                                $scheduledOnOtherDates[] = strtolower(trim($s));
                            }
                        }
                    }
                }
                $scheduledOnOtherDates = array_unique($scheduledOnOtherDates);
                
                // Also get what's currently assigned to THIS date/grade (to show as pills, not filter)
                $currentlyAssigned = [];
                foreach($gradeClassIds as $cid) {
                    $subj = $scheduleMatrix[$inlineEditDate][$cid] ?? null;
                    if ($subj && $subj !== '-' && $subj !== 'Holiday') {
                        foreach(explode(', ', $subj) as $s) {
                            $currentlyAssigned[] = trim($s);
                        }
                    }
                }
                $currentlyAssigned = array_unique($currentlyAssigned);
                
                // Get subjects for the current grade
                $gradeSubjectsForModal = $gradeSubjects[$inlineEditGrade] ?? [];
                
                // Filter: Available = Grade subjects NOT scheduled on other dates AND NOT currently assigned
                $availableSubjectsForModal = array_filter($gradeSubjectsForModal, function($s) use ($scheduledOnOtherDates, $currentlyAssigned) {
                    $lower = strtolower($s);
                    return !in_array($lower, $scheduledOnOtherDates) && !in_array($s, $currentlyAssigned);
                });
                
                // Get grade name for display
                $gradeDisplay = $inlineEditGrade . 'th';
                $dateDisplay = \Carbon\Carbon::parse($inlineEditDate)->format('D, d M Y');
            @endphp
            <div 
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                x-data
                @keydown.escape.window="$wire.closeInlineEditor()"
            >
                {{-- Backdrop --}}
                <div 
                    class="fixed inset-0 bg-black/50 transition-opacity"
                    wire:click="closeInlineEditor"
                ></div>
                
                {{-- Modal Content --}}
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                    {{-- Header with Grade & Date --}}
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold">Assign Subject</h3>
                                <p class="text-sm text-indigo-100 mt-0.5">
                                    Class {{ $gradeDisplay }} &bull; {{ $dateDisplay }}
                                </p>
                            </div>
                            <button 
                                wire:click="closeInlineEditor"
                                class="text-white/80 hover:text-white p-1 rounded-full hover:bg-white/20 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    {{-- Quick Actions --}}
                    <div class="flex gap-3 px-6 py-3 bg-gray-50 border-b border-gray-200">
                        <button 
                            wire:click="markHoliday('{{ $inlineEditDate }}', '{{ $inlineEditGrade }}')"
                            class="flex-1 px-4 py-2 text-sm font-semibold bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 transition flex items-center justify-center gap-2"
                        >
                            <span>🏖</span> Holiday
                        </button>
                        <button 
                            wire:click="clearAssignment('{{ $inlineEditDate }}', '{{ $inlineEditGrade }}')"
                            class="flex-1 px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Clear
                        </button>
                    </div>
                    
                    {{-- Subject List --}}
                    <div class="px-6 py-4 max-h-72 overflow-y-auto">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Available Subjects</p>
                        
                        @forelse($availableSubjectsForModal as $subj)
                            <button 
                                wire:click="assignSubjectInline('{{ $subj }}')"
                                class="w-full text-left px-4 py-3 mb-2 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition flex items-center justify-between group"
                            >
                                <span class="font-medium text-gray-800">{{ $subj }}</span>
                                <span class="text-indigo-500 opacity-0 group-hover:opacity-100 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </span>
                            </button>
                        @empty
                            <div class="text-center py-8 text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="font-medium">All subjects assigned</p>
                                <p class="text-sm mt-1">Every subject for this date is already scheduled</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
        
        {{-- MARKS CONFIG MATRIX --}}
        @elseif($viewMode === 'marks')
        <div class="space-y-6">
            <p class="text-sm text-gray-500">Set total marks for each subject per class.</p>
            
            @foreach($groupedClasses as $grade => $classes)
                @foreach($classes as $class)
                    @php $classId = is_array($class) ? $class['id'] : $class->id; @endphp
                    
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                        {{-- Card Header --}}
                        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="text-lg font-bold text-gray-800">{{ $class['name'] }}</h3>
                            <button 
                                wire:click="autoFillSubjects('{{ $classId }}')"
                                class="text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded transition-colors"
                            >
                                Auto-fill Subjects
                            </button>
                        </div>
                        
                        {{-- Card Body --}}
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @if(isset($marksMatrix[$classId]) && count($marksMatrix[$classId]) > 0)
                                    @foreach($marksMatrix[$classId] as $subject => $marksData)
                                        <div class="bg-gray-50 hover:bg-white p-3 rounded-lg border border-gray-200 transition-all shadow-sm group">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex justify-between items-start">
                                                    <span class="text-sm font-bold text-gray-800 break-words leading-tight pt-1 pr-2" title="{{ $subject }}">
                                                        {{ $subject }}
                                                    </span>
                                                    <button 
                                                        wire:click="removeSubject('{{ $classId }}', '{{ $subject }}')"
                                                        class="text-gray-300 hover:text-red-500 transition-colors -mt-1 -mr-1 p-1"
                                                        title="Remove Subject"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>
                                                
                                                <div class="flex items-center justify-between gap-2 bg-white p-2 rounded border border-gray-100 mt-1">
                                                    {{-- Total Marks --}}
                                                    <div class="flex flex-col w-1/2">
                                                        <label class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-0.5">Total</label>
                                                        <input 
                                                            type="number" 
                                                            value="{{ $marksData['total'] ?? 75 }}"
                                                            wire:blur="updateMark('{{ $classId }}', '{{ $subject }}', 'total', $event.target.value)"
                                                            class="w-full text-center border-gray-200 focus:border-indigo-500 focus:ring-0 rounded text-sm font-semibold text-gray-700 h-7 p-0"
                                                            placeholder="75"
                                                        >
                                                    </div>
                                                    
                                                    <div class="w-px h-8 bg-gray-100 my-auto"></div>

                                                    {{-- Passing Marks --}}
                                                    <div class="flex flex-col w-1/2">
                                                        <label class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-0.5 text-right pr-1">Pass</label>
                                                        <input 
                                                            type="number" 
                                                            value="{{ $marksData['passing'] ?? 33 }}"
                                                            wire:blur="updateMark('{{ $classId }}', '{{ $subject }}', 'passing', $event.target.value)"
                                                            class="w-full text-center border-gray-200 focus:border-red-500 focus:ring-0 rounded text-sm font-semibold text-red-600 h-7 p-0"
                                                            placeholder="33"
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-span-full text-center py-4 text-sm text-gray-400 italic">
                                        No subjects configured. Click "Auto-fill" or add manually below.
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Add Subject Footer --}}
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <form wire:submit.prevent="addSubject('{{ $classId }}')" class="flex gap-2">
                                    <input 
                                        type="text" 
                                        wire:model="newSubjectNames.{{ $classId }}" 
                                        placeholder="New Subject Name" 
                                        class="flex-1 border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm text-sm"
                                    >
                                    <button 
                                        type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Add
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
        @endif
    </div>

    {{-- ASSIGNMENT MODAL --}}
    @if($showAssignModal)
    <div 
        class="fixed inset-0 z-50 overflow-y-auto" 
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true"
        x-data
        @keydown.escape.window="$wire.set('showAssignModal', false)"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showAssignModal', false)"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Assign Subject
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Assigning for <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}</strong>
                                </p>
                            </div>

                            {{-- Subject Input --}}
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Subject Name</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input 
                                        type="text" 
                                        wire:model="subjectInput" 
                                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" 
                                        placeholder="e.g. Mathematics" 
                                        list="subjectOptions"
                                        autofocus
                                        @keydown.enter="$wire.saveAssignment()"
                                    >
                                    <datalist id="subjectOptions">
                                        <option value="Holiday">
                                        @foreach($availableSubjects as $subject)
                                            <option value="{{ $subject }}">
                                        @endforeach
                                    </datalist>
                                </div>
                            </div>

                            {{-- Class Selection --}}
                            <div class="mt-6">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-gray-700">Apply to Classes</label>
                                    <div class="text-xs text-indigo-600 space-x-2">
                                        <button wire:click="$set('targetClasses', @js($allClassIds))" class="hover:underline">Select All</button>
                                        <button wire:click="$set('targetClasses', [])" class="hover:underline">Select None</button>
                                    </div>
                                </div>
                                
                                <div class="border border-gray-200 rounded-md max-h-48 overflow-y-auto p-2 bg-gray-50 space-y-3">
                                    @foreach($groupedClasses as $classes)
                                        <div>
                                            @php $grade = preg_replace('/[^0-9]/', '', $classes[0]['name'] ?? '0'); @endphp
                                            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Class {{ $grade }}</div>
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach($classes as $class)
                                                    @php 
                                                        $classId = is_array($class) ? $class['id'] : $class->id;
                                                        $className = is_array($class) ? $class['name'] : $class->name;
                                                    @endphp
                                                    <label class="inline-flex items-center p-2 rounded hover:bg-white hover:shadow-sm cursor-pointer transition-all">
                                                        <input 
                                                            type="checkbox" 
                                                            wire:model="targetClasses" 
                                                            value="{{ $classId }}" 
                                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                        >
                                                        <span class="ml-2 text-sm text-gray-700">{{ $className }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button 
                        type="button" 
                        wire:click="saveAssignment"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Save Assignment
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('showAssignModal', false)"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
