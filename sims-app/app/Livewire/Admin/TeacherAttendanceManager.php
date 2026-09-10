<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherAttendanceManager extends Component
{
    public $date;
    public $activeTab = 'attendance';
    public $dailyNote = '';
    public $showNoteModal = false;
    public $teachers = [];
    public $attendanceData = []; // [user_id => status]
    public $remarksData = []; // [user_id => remarks]
    public $savedTeacherIds = []; // IDs of teachers with saved attendance for this date
    
    protected $rules = [
        'attendanceData.*' => 'required|in:present,absent,short_leave,leave,official_duty',
        'remarksData.*' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->reportFromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->reportToDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadTeachersAndAttendance();
        $this->loadPeriodLabels();
    }

    public function updatedDate()
    {
        $this->loadTeachersAndAttendance();
        $this->loadPeriodLabels();
    }

    public function loadTeachersAndAttendance()
    {
        // Get all teachers
        $this->teachers = User::where('role', 'teacher')->orderBy('name')->get();
        
        // Get existing attendance for this date
        $attendances = TeacherAttendance::where('date', $this->date)->get();

        $this->attendanceData = [];
        $this->remarksData = [];
        $this->savedTeacherIds = [];

        foreach ($this->teachers as $teacher) {
            $record = $attendances->firstWhere('user_id', $teacher->id);
            if ($record) {
                $this->attendanceData[$teacher->id] = $record->status;
                $this->remarksData[$teacher->id] = $record->remarks;
                $this->savedTeacherIds[] = $teacher->id;
            } else {
                // Default to present if no record exists yet
                $this->attendanceData[$teacher->id] = 'present';
                $this->remarksData[$teacher->id] = '';
            }
        }
        // Load Daily Note
        $dailyNoteRecord = \App\Models\DailyNote::where('date', $this->date)->first();
        $this->dailyNote = $dailyNoteRecord ? $dailyNoteRecord->note : '';
    }

    public function saveAttendance()
    {
        $this->validate();
        
        DB::beginTransaction();
        try {
            foreach ($this->attendanceData as $userId => $status) {
                TeacherAttendance::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'date' => $this->date
                    ],
                    [
                        'status' => $status,
                        'remarks' => $this->remarksData[$userId] ?? null
                    ]
                );
            }
            DB::commit();
            
            // Save Daily Note
            if (!empty($this->dailyNote)) {
                \App\Models\DailyNote::updateOrCreate(
                    ['date' => $this->date],
                    ['note' => $this->dailyNote]
                );
            } else {
                // Optional: Delete if empty? Or just keep empty string.
                 \App\Models\DailyNote::updateOrCreate(
                    ['date' => $this->date],
                    ['note' => null] // or empty string
                );
            }

            $this->loadTeachersAndAttendance(); // Refresh saved state
            session()->flash('message', 'Attendance saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error saving attendance: ' . $e->getMessage());
        }
    }

    public function saveNote()
    {
        // Save Daily Note
        \App\Models\DailyNote::updateOrCreate(
            ['date' => $this->date],
            ['note' => $this->dailyNote]
        );
        $this->showNoteModal = false;
        session()->flash('message', 'Daily note saved.');
    }

    public function markAllPresent()
    {
        foreach ($this->teachers as $teacher) {
            $this->attendanceData[$teacher->id] = 'present';
        }
    }



    // Substitution Logic
    public $showSubstitutionModal = false;
    public $manageForTeacherId;
    public $manageForTeacherName;
    public $substitutions = []; // [timetable_id => substitute_teacher_id]
    public $substitutionData = []; // To store fetched timetable and suggestions
    public $periodLabels = [];
    public $dailySubstitutionCounts = []; // [teacher_id => count]

    public function openSubstitutionModal($teacherId)
    {
        Log::info("openSubstitutionModal called for Teacher ID: $teacherId");
        $this->manageForTeacherId = $teacherId;
        $teacher = $this->teachers->firstWhere('id', $teacherId);
        $this->manageForTeacherName = $teacher ? $teacher->name : 'Unknown';
        
        // 1. Get Today's Schedule for this Teacher from Timetable
        // We need to know which ScheduleTemplate is active today?
        // For MVP, let's assume one active template or use the day of week.
        $dayOfWeek = Carbon::parse($this->date)->format('l');
        
        // Fetch Daily Merges
        $dailyMerges = \App\Models\DailyClassMerge::where('date', $this->date)
            ->get();
        
        // Fetch Daily Substitution Counts
        $this->dailySubstitutionCounts = \Illuminate\Support\Facades\DB::table('substitutions')
            ->where('date', $this->date)
            ->select('substitute_teacher_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('substitute_teacher_id')
            ->pluck('count', 'substitute_teacher_id')
            ->toArray();
        
        $mergedSourceIds = $dailyMerges->pluck('source_timetable_id')->toArray();
        $mergedTargets = $dailyMerges->pluck('source_timetable_id', 'target_timetable_id')->toArray();

        // Explicitly fetch closed class IDs
        $closedClassIds = \App\Models\ClosedClassroom::where('date', $this->date)->pluck('class_id')->toArray();
        
        $todaysClasses = \App\Models\Timetable::with(['class', 'subject', 'template'])
            ->where('teacher_id', $teacherId)
            ->where('day', $dayOfWeek)
            // Ideally filter by active template too, but if we just take all, 
            // we might get multiple if there are multiple templates.
            // Let's assume there's one active template generally or we fetch from TimetableManager logic.
            ->whereHas('template', function($q) {
                $q->where('is_active', true);
            })

            ->whereNotIn('id', $mergedSourceIds) // Exclude if merged away
            ->whereNotIn('class_id', $closedClassIds) // Restore: Exclude if class is CLOSED
            ->orderBy('period_no')
            ->get();

        // Fetch period labels
        $templateIds = $todaysClasses->pluck('schedule_template_id')->unique();
        $this->periodLabels = \App\Models\PeriodConfig::whereIn('schedule_template_id', $templateIds)
            ->pluck('label', 'period_no')
            ->toArray();

        // 2. For each period, group classes and find free teachers
        $this->substitutionData = [];
        $this->substitutions = []; // Reset selections

        $groupedByPeriod = $todaysClasses->groupBy('period_no');

        foreach ($groupedByPeriod as $periodNo => $classes) {
            
            $timetableIds = $classes->pluck('id')->toArray();
            $names = $classes->map(fn($c) => $c->class->name)->toArray();
            $classNames = $this->formatCombinedClassNames($names);
            $subjectNames = $classes->map(fn($c) => $c->subject->name)->unique()->implode(' / ');

            // Check if there is already a substitution for any of these timetable_ids
            $existingSub = \Illuminate\Support\Facades\DB::table('substitutions')
                ->where('date', $this->date)
                ->whereIn('timetable_id', $timetableIds)
                ->first();

            if ($existingSub) {
                $this->substitutions["period_{$periodNo}"] = $existingSub->substitute_teacher_id;
            }

            // Find free teachers (using the first timetable in group for context)
            $firstClass = $classes->first();
            $freeTeachers = $this->getFreeTeachers($periodNo, $dayOfWeek, $firstClass->schedule_template_id, $firstClass->id);

            $this->substitutionData[] = [
                'period_no' => $periodNo,
                'timetable_ids' => $timetableIds,
                'class_names' => $classNames,
                'subject_names' => $subjectNames,
                'free_teachers' => $freeTeachers,
                'existing_sub' => $existingSub
            ];
        }

        $this->showSubstitutionModal = true;
    }

    public function getFreeTeachers($periodNo, $day, $templateId, $excludeTimetableId = null)
    {
        // 1. Get all teachers
        // 2. Exclude teachers who have a class at this period/day/template
        // 3. Exclude teachers who are absent/leave/short_leave TODAY
        
        // Fetch Daily Merges for this date
        $dailyMerges = \App\Models\DailyClassMerge::where('date', $this->date)
             ->get();
        // Get IDs of timetables that are merged away (source)
        $mergedSourceTimetableIds = $dailyMerges->pluck('source_timetable_id')->toArray();

        // Get IDs of classes that are CLOSED for this date
        $closedClassIds = \App\Models\ClosedClassroom::where('date', $this->date)->pluck('class_id')->toArray();

        $busyTeacherIds = \App\Models\Timetable::where('day', $day)
            ->where('period_no', $periodNo)
            // Check ALL active templates
            ->whereHas('template', fn($q) => $q->where('is_active', true))
            ->whereNotIn('id', $mergedSourceTimetableIds) // If merged away, teacher is NOT busy
            ->whereNotIn('class_id', $closedClassIds) // If class is CLOSED, teacher is NOT busy
            ->pluck('teacher_id')
            ->toArray();

        // 4. Exclude teachers already assigned as substitutes for this date and period
        $substBusyQuery = \Illuminate\Support\Facades\DB::table('substitutions')
            ->join('timetables', 'substitutions.timetable_id', '=', 'timetables.id')
            ->where('substitutions.date', $this->date)
            ->where('timetables.period_no', $periodNo);

        if ($excludeTimetableId) {
            $substBusyQuery->where('substitutions.timetable_id', '!=', $excludeTimetableId);
        }

        $substBusyTeacherIds = $substBusyQuery->pluck('substitute_teacher_id')->toArray();

        // Also exclude absent teachers (now only absent and leave)
        $absentTeacherIds = [];
        foreach ($this->attendanceData as $tId => $status) {
            // OD and SL teachers are available to teach, so we don't exclude them from substitution pool
            if (in_array($status, ['absent', 'leave'])) {
                $absentTeacherIds[] = $tId;
            }
        }
        
        $allBusyIds = array_unique(array_merge($busyTeacherIds, $absentTeacherIds, $substBusyTeacherIds));

        return $this->teachers->whereNotIn('id', $allBusyIds);
    }

    public function saveSubstitutions()
    {
        foreach ($this->substitutions as $key => $subTeacherId) {
            // Key is in format "period_{periodNo}"
            if (!str_starts_with($key, 'period_')) continue;
            
            $periodNo = str_replace('period_', '', $key);
            
            // Find the timetable IDs for this period from substitutionData
            $periodData = collect($this->substitutionData)->firstWhere('period_no', (int)$periodNo);
            if (!$periodData) continue;

            $timetableIds = $periodData['timetable_ids'];

            foreach ($timetableIds as $timetableId) {
                if ($subTeacherId) {
                    $attendanceRecord = TeacherAttendance::firstOrCreate(
                        ['user_id' => $this->manageForTeacherId, 'date' => $this->date],
                        ['status' => $this->attendanceData[$this->manageForTeacherId] ?? 'absent']
                    );

                    \Illuminate\Support\Facades\DB::table('substitutions')->updateOrInsert(
                        [
                            'date' => $this->date,
                            'timetable_id' => $timetableId,
                        ],
                        [
                            'teacher_attendance_id' => $attendanceRecord->id,
                            'substitute_teacher_id' => $subTeacherId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                } else {
                     \Illuminate\Support\Facades\DB::table('substitutions')
                        ->where('date', $this->date)
                        ->where('timetable_id', $timetableId)
                        ->delete();
                }
            }
        }
        
        $this->showSubstitutionModal = false;
        session()->flash('message', 'Substitutions saved.');
    }

    // Merge Logic
    public $showMergeModal = false;
    public $mergeDate;
    public $mergePeriod;
    public $mergeSourceClassId;
    public $mergeTargetClassId;
    public $periods = [];
    public $classes = [];
    public $mergedClassesList = [];

    public function openMergeModal()
    {
        $this->mergeDate = $this->date;
        $this->loadPeriodLabels(); // Ensure labels are loaded
        // Get all classes, sorted by numeric sequence
        $this->classes = \App\Models\Classes::orderBy('numeric_value')
            ->orderBy('name')
            ->get();
        // Get periods from active template
        $activeTemplate = \App\Models\ScheduleTemplate::where('is_active', true)->first();
        if ($activeTemplate) {
            $this->periods = \App\Models\PeriodConfig::where('schedule_template_id', $activeTemplate->id)
                ->orderBy('period_no')
                ->get();
        }
        
        $this->loadMergedClasses();
        
        $this->reset(['mergePeriod', 'mergeSourceClassId', 'mergeTargetClassId']);
        $this->showMergeModal = true;
    }

    public function loadMergedClasses()
    {
        $merges = \App\Models\DailyClassMerge::with(['sourceTimetable.class', 'targetTimetable.class'])
            ->where('date', $this->mergeDate ?? $this->date)
            ->get();
            
        $grouped = [];
        foreach ($merges as $merge) {
            if ($merge->sourceTimetable && $merge->targetTimetable) {
                $sourceId = $merge->sourceTimetable->class_id;
                $targetId = $merge->targetTimetable->class_id;
                $key = $sourceId . '_' . $targetId;
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'source_class_id' => $sourceId,
                        'target_class_id' => $targetId,
                        'source_class_name' => $merge->sourceTimetable->class->name,
                        'target_class_name' => $merge->targetTimetable->class->name,
                        'count' => 0
                    ];
                }
                $grouped[$key]['count']++;
            }
        }
        $this->mergedClassesList = array_values($grouped);
    }

    public function unmergeClasses($sourceClassId, $targetClassId)
    {
        \App\Models\DailyClassMerge::where('date', $this->mergeDate ?? $this->date)
            ->whereHas('sourceTimetable', function($q) use ($sourceClassId) {
                $q->where('class_id', $sourceClassId);
            })
            ->whereHas('targetTimetable', function($q) use ($targetClassId) {
                $q->where('class_id', $targetClassId);
            })->delete();
            
        $this->loadMergedClasses();
        session()->flash('message', 'Classes unmerged successfully.');
    }

    public function saveMerge()
    {
        $this->validate([
            'mergeDate' => 'required|date',
            'mergeSourceClassId' => 'required|different:mergeTargetClassId',
            'mergeTargetClassId' => 'required',
        ]);

        $dayOfWeek = Carbon::parse($this->mergeDate)->format('l');

        // Fetch all source class timetable entries for the day
        $sourceTimetables = \App\Models\Timetable::where('class_id', $this->mergeSourceClassId)
            ->where('day', $dayOfWeek)
            ->whereHas('template', fn($q) => $q->where('is_active', true))
            ->get();

        $mergesCount = 0;

        foreach ($sourceTimetables as $sourceTimetable) {
            // Find corresponding target class entry for the same period
            $targetTimetable = \App\Models\Timetable::where('class_id', $this->mergeTargetClassId)
                ->where('period_no', $sourceTimetable->period_no)
                ->where('day', $dayOfWeek)
                ->whereHas('template', fn($q) => $q->where('is_active', true))
                ->first();

            if ($targetTimetable) {
                // Check if already merged
                $exists = \App\Models\DailyClassMerge::where('date', $this->mergeDate)
                    ->where('source_timetable_id', $sourceTimetable->id)
                    ->exists();
                
                if (!$exists) {
                    \App\Models\DailyClassMerge::create([
                        'date' => $this->mergeDate,
                        'source_timetable_id' => $sourceTimetable->id,
                        'target_timetable_id' => $targetTimetable->id,
                    ]);
                    $mergesCount++;
                }
            }
        }

        if ($mergesCount == 0) {
            $this->addError('mergeSourceClassId', 'No matching periods found to merge between these classes.');
            return;
        }

        $this->loadMergedClasses();
        $this->reset(['mergeSourceClassId', 'mergeTargetClassId']);
        session()->flash('message', "Merged $mergesCount periods successfully for today.");
    }

    // Close Classroom Logic
    public $showCloseClassModal = false;
    public $closeClassId;
    public $closeClassReason;
    public $closedClasses = [];

    public function openCloseClassModal()
    {
        $this->loadClosedClasses();
        // Load classes if not already loaded (used in merge modal too)
        if (empty($this->classes)) {
             $this->classes = \App\Models\Classes::orderBy('numeric_value')->orderBy('name')->get();
        }
        $this->reset(['closeClassId', 'closeClassReason']);
        $this->showCloseClassModal = true;
    }

    public function loadClosedClasses()
    {
        $this->closedClasses = \App\Models\ClosedClassroom::with('class')
            ->where('date', $this->date)
            ->get();
    }

    public function saveCloseClass()
    {
        if (in_array($this->closeClassId, ['school_all', 'college_all']) || preg_match('/^grade_all_(\d+)$/', $this->closeClassId, $gradeMatch)) {
            $allClasses = \App\Models\Classes::all();
            $targetClassIds = [];

            foreach ($allClasses as $class) {
                // Extract number from "Class 10B"
                if (preg_match('/(\d+)/', $class->name, $matches)) {
                    $grade = (int)$matches[1];

                    if (isset($gradeMatch[1])) {
                        // Close all sections of a single grade (e.g. grade_all_6 closes 6A, 6B, 6C, 6D)
                        if ($grade === (int)$gradeMatch[1]) {
                            $targetClassIds[] = $class->id;
                        }
                    } elseif ($this->closeClassId === 'school_all' && $grade >= 6 && $grade <= 10) {
                        $targetClassIds[] = $class->id;
                    } elseif ($this->closeClassId === 'college_all' && ($grade == 11 || $grade == 12)) {
                        $targetClassIds[] = $class->id;
                    }
                }
            }

            foreach ($targetClassIds as $classId) {
                \App\Models\ClosedClassroom::updateOrInsert(
                    ['class_id' => $classId, 'date' => $this->date],
                    ['reason' => $this->closeClassReason ?? 'Bulk Closure', 'created_at' => now(), 'updated_at' => now()]
                );
            }

            $this->loadClosedClasses();
            $this->reset(['closeClassId', 'closeClassReason']);
            session()->flash('message', 'Bulk classes closed successfully.');
            return;
        }

        $this->validate([
            'closeClassId' => 'required|exists:classes,id',
            'closeClassReason' => 'nullable|string|max:255',
        ]);

        // Check if already closed
        $exists = \App\Models\ClosedClassroom::where('date', $this->date)
            ->where('class_id', $this->closeClassId)
            ->exists();

        if ($exists) {
            $this->addError('closeClassId', 'This class is already closed for this date.');
            return;
        }

        \App\Models\ClosedClassroom::create([
            'class_id' => $this->closeClassId,
            'date' => $this->date,
            'reason' => $this->closeClassReason,
        ]);

        $this->loadClosedClasses();
        $this->reset(['closeClassId', 'closeClassReason']);
        session()->flash('message', 'Class closed successfully.');
    }

    public function deleteClosedClass($id)
    {
        \App\Models\ClosedClassroom::destroy($id);
        $this->loadClosedClasses();
        session()->flash('message', 'Class re-opened successfully.');
    }

    public function loadPeriodLabels()
    {
        $this->periodLabels = \App\Models\PeriodConfig::whereHas('template', function($q) {
            $q->where('is_active', true);
        })->pluck('label', 'period_no')->toArray();
    }

    public function getArrangementsProperty()
    {
        // 1. Get absent teacher IDs (Now including OD and SL as requested for substitution management)
        $absentTeacherIds = [];
        foreach ($this->attendanceData as $teacherId => $status) {
            if (in_array($status, ['absent', 'leave', 'official_duty', 'short_leave'])) {
                $absentTeacherIds[] = $teacherId;
            }
        }

        if (empty($absentTeacherIds)) {
            return [];
        }

        $dayOfWeek = Carbon::parse($this->date)->format('l');

        // 2. Get timetables for these teachers for today
        $timetables = \App\Models\Timetable::with(['class', 'subject', 'template'])
            ->whereIn('teacher_id', $absentTeacherIds)
            ->where('day', $dayOfWeek)
            ->whereHas('template', function($q) {
                $q->where('is_active', true);
            })

            ->whereNotIn('class_id', \App\Models\ClosedClassroom::where('date', $this->date)->pluck('class_id'))
            ->orderBy('period_no')
            ->get();

        // 3. Get Substitutions for today
        $substitutions = \Illuminate\Support\Facades\DB::table('substitutions')
            ->where('date', $this->date)
            ->whereIn('timetable_id', $timetables->pluck('id'))
            ->get()
            ->keyBy('timetable_id');

        // Fetch Daily Substitution Counts
        $dailyCounts = \Illuminate\Support\Facades\DB::table('substitutions')
            ->where('date', $this->date)
            ->select('substitute_teacher_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('substitute_teacher_id')
            ->pluck('count', 'substitute_teacher_id')
            ->toArray();

        // 4. Map to result
        // Include ALL non-present teachers (absent/leave/OD/SL), even those with no
        // scheduled classes today, so every absent teacher is accounted for.
        // OD/SL teachers are shown even without a substitute, so the admin can see
        // which classes still need to be covered.
        $results = [];

        foreach ($absentTeacherIds as $teacherId) {
            $teacher = $this->teachers->firstWhere('id', $teacherId);
            if (!$teacher) continue;

            $teacherTimetables = $timetables->where('teacher_id', $teacherId);

            $formattedClasses = $teacherTimetables->groupBy('period_no')->map(function($periodClasses) use ($substitutions, $dailyCounts) {
                $firstClass = $periodClasses->first();
                $sub = $substitutions->get($firstClass->id);
                $subTeacherName = null;
                if ($sub) {
                    $subTeacher = $this->teachers->firstWhere('id', $sub->substitute_teacher_id);
                    $count = $dailyCounts[$sub->substitute_teacher_id] ?? 0;
                    $subTeacherName = $subTeacher ? $subTeacher->name . " ($count)" : 'Unknown';
                }

                $names = $periodClasses->map(fn($c) => $c->class->name)->toArray();
                $className = $this->formatCombinedClassNames($names);

                return [
                    'period_no' => $firstClass->period_no,
                    'class_name' => $className,
                    'subject_name' => $periodClasses->pluck('subject.name')->unique()->implode(' / '),
                    'sub_teacher_name' => $subTeacherName,
                    'has_sub' => $subTeacherName !== null
                ];
            })->values();

            $results[] = [
                'teacher_name' => $teacher->name,
                'status' => $this->attendanceData[$teacherId] ?? 'absent',
                'remarks' => $this->remarksData[$teacherId] ?? '',
                'classes' => $formattedClasses
            ];
        }

        return $results;
    }

    public function downloadPdf()
    {
        $this->saveAttendance();
        $url = route('admin.teacher-attendance.pdf', ['date' => $this->date]);
        $this->dispatch('open-pdf', url: $url);
    }

    // Arrangement Report Logic
    public $reportFromDate;
    public $reportToDate;
    public $reportData = [];

    public function generateReport()
    {
        $this->validate([
            'reportFromDate' => 'required|date',
            'reportToDate' => 'required|date|after_or_equal:reportFromDate',
        ]);

        // Get substitution counts grouped by teacher
        $substitutionCounts = \Illuminate\Support\Facades\DB::table('substitutions')
            ->whereBetween('date', [$this->reportFromDate, $this->reportToDate])
            ->select('substitute_teacher_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('substitute_teacher_id')
            ->pluck('count', 'substitute_teacher_id')
            ->toArray();

        // Get all teachers and map the counts
        $this->reportData = $this->teachers->map(function ($teacher) use ($substitutionCounts) {
            return [
                'name' => $teacher->name,
                'count' => $substitutionCounts[$teacher->id] ?? 0,
            ];
        })->sortByDesc('count')->values()->toArray();
    }

    public function formatCombinedClassNames($names)
    {
        if (count($names) <= 1) return $names[0] ?? '';
        
        $cleanedNames = array_map(fn($n) => str_replace('Class ', '', $n), $names);
        
        $prefixes = [];
        $suffixes = [];
        
        foreach ($cleanedNames as $name) {
            if (preg_match('/^(\d+)(.*)$/', $name, $matches)) {
                $prefixes[] = $matches[1];
                $suffixes[] = $matches[2];
            } else {
                return implode('+', $cleanedNames);
            }
        }
        
        if (count(array_unique($prefixes)) === 1) {
            return $prefixes[0] . '(' . implode('+', $suffixes) . ')';
        }
        
        return implode('+', $cleanedNames);
    }

    public function render()
    {
        return view('livewire.admin.teacher-attendance-manager')
            ->layout('components.layouts.admin', ['title' => 'Teacher Attendance']);
    }
}
