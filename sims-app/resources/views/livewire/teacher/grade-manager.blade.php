<div class="space-y-6 max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gradebook</h1>
            <p class="text-gray-500">Manage student grades and performance</p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-green-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-xl text-red-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="glass-card p-6 rounded-2xl space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Exam --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
                <select wire:model.live="selectedExamId" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="">Select Exam</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Class --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select 
                    wire:model.live="selectedClassId" 
                    class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                    @if(!$selectedExamId) disabled @endif
                >
                    <option value="">Select Class</option>
                    @foreach($availableClasses as $class)
                        <option value="{{ $class->id }}">
                            {{ $class->name }}
                            @if($isClassTeacher && $class->id == auth()->user()->class_id)
                                (Class Teacher)
                            @endif
                        </option>
                    @endforeach
                </select>
                @if($availableClasses->isEmpty() && $selectedExamId)
                    <p class="text-xs text-red-500 mt-1">No classes assigned to you for this exam</p>
                @endif
            </div>

            {{-- Subject --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <select 
                    wire:model.live="selectedSubjectId" 
                    class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                    @if(!$selectedClassId) disabled @endif
                >
                    <option value="">Select Subject</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Grades Table --}}
    @if($selectedSubjectId && count($students) > 0)
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">
                Marking Grade
                <span class="text-sm font-normal text-gray-500 ml-2">(Max: {{ $maxMarks }} | Pass: {{ round($passingScore, 1) }} marks / {{ $passingMarks }}%)</span>
            </h3>
            
            <div class="flex items-center gap-2">
            @if(!$isLocked)
            <button
                wire:click="save"
                wire:loading.attr="disabled"
                class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-bold shadow-lg shadow-blue-200 flex items-center gap-2 disabled:opacity-50"
            >
                <span wire:loading.remove>Save Grades</span>
                <span wire:loading>Saving...</span>
            </button>
            @endif

            @if(!$isLocked && $isGradingAllowed)
            @endif
            </div>
        </div>

            @if(!$isGradingAllowed)
            <div class="px-6 py-3 bg-yellow-50 border-b border-yellow-200">
                <div class="flex items-center gap-2 text-yellow-800 text-sm font-medium">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    This exam is "Upcoming". Grading is currently locked.
                </div>
            </div>
            @endif

            @if($isLocked)
            <div class="px-6 py-3 bg-red-50 border-b border-red-200">
                <div class="flex items-center gap-2 text-red-800 text-sm font-medium">
                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    This gradebook is LOCKED. Editing is disabled.
                </div>
            </div>
            @endif

            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Roll No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Absent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Marks Obtained</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">%</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Grade</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($students as $student)
                    @php
                        $isAbsent = $absents[$student->id] ?? false;
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $isAbsent ? 'bg-orange-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $student->roll_no ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $student->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <input
                                type="checkbox"
                                wire:model.live="absents.{{ $student->id }}"
                                class="w-5 h-5 text-orange-500 border-gray-300 rounded focus:ring-orange-500"
                                @if(!$isGradingAllowed || $isLocked) disabled @endif
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input
                                type="number"
                                wire:model.defer="grades.{{ $student->id }}"
                                min="0"
                                max="{{ $maxMarks }}"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium disabled:bg-gray-100 disabled:text-gray-400"
                                placeholder="0-{{ $maxMarks }}"
                                @if(!$isGradingAllowed || $isAbsent || $isLocked) disabled @endif
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{-- Calculate percentage --}}
                            @php
                                $mark = $grades[$student->id] ?? null;
                                $pct = null;
                                if (!$isAbsent && $mark !== '' && $mark !== null && $maxMarks > 0) {
                                    $pct = round(((float)$mark / $maxMarks) * 100, 1);
                                }
                            @endphp
                            @if($isAbsent)
                                <span class="text-red-500 font-medium">A</span>
                            @elseif($pct !== null)
                                <span class="font-medium">{{ $pct }}%</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                            {{-- Auto-calculate grade using per-subject passing marks --}}
                            @php
                                $grade = '-';
                                $color = 'text-gray-400';
                                
                                if ($isAbsent) {
                                    $grade = 'A';
                                    $color = 'text-red-500';
                                } elseif ($pct !== null) {
                                    // Grade based on percentage with passing threshold
                                    if ($pct >= 90) { $grade = 'A+'; $color = 'text-green-600'; }
                                    elseif ($pct >= 80) { $grade = 'A'; $color = 'text-green-500'; }
                                    elseif ($pct >= 70) { $grade = 'B+'; $color = 'text-blue-600'; }
                                    elseif ($pct >= 60) { $grade = 'B'; $color = 'text-blue-500'; }
                                    elseif ((float)$mark >= $passingScore) { $grade = 'C'; $color = 'text-yellow-600'; }
                                    else { $grade = 'F'; $color = 'text-red-500'; }
                                }
                            @endphp
                            <span class="{{ $color }}">{{ $grade }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @elseif($selectedSubjectId)
        <div class="p-12 text-center text-gray-500 bg-white rounded-2xl border border-gray-100 mx-6">
            <p>No students found in this class.</p>
        </div>
    @endif
</div>
