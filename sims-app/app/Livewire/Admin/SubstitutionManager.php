<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\User;
use App\Models\TeacherAttendance;
use App\Models\Substitution;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\ClosedClassroom;
use App\Models\DailyClassMerge;
use Carbon\Carbon;

class SubstitutionManager extends Component
{
    // Tab Navigation: 'attendance', 'arrangement', 'reports'
    public $activeTab = 'attendance';
    // Report Sub-Tab: 'monthly_attendance', 'teacher_report', 'workload'
    public $reportTab = 'monthly_attendance';
    // Selected teacher for Individual Teacher Attendance & Remarks Report
    public $selectedTeacherId = null;
    public $excludeWeekends = true; // Exclude weekend days from Daily Log & Remarks by default

    public $selectedDate;
    public $selectedSessionId;
    public $academicSessions = [];
    
    public $teachers = [];
    public $teacherStatuses = []; // [teacher_id => status]
    public $teacherRemarks = [];  // [teacher_id => string]
    
    // Structure: [teacher_id => [period_no => substitute_teacher_id]]
    public $substitutions = [];
    
    // Structure: [teacher_id => [['period_no' => x, 'class_name' => y]]]
    public $teacherAssignedSubs = [];

    // Workload counters
    public $dailySubCounts = [];
    public $monthlySubCounts = [];

    // UI toggle: show/hide monthly count column in the workload panel
    public $showMonthlyCount = true;

    // Toggles for "Show All Teachers" per period assignment
    public $showAllTeachersToggle = [];

    // Notifications & focus
    public $warningMessage = '';
    public $focusedTeacherId = null;

    // Close Classroom State
    public $showCloseClassModal = false;
    public $closeClassId = '';
    public $closeClassReason = '';
    public $closedClassesList = [];

    // Merge Classroom State
    public $showMergeModal = false;
    public $mergeSourceClassId = '';
    public $mergeTargetClassId = '';
    public $mergedClassesList = [];

    // Classes for current shift & session
    public $classesForCurrentShift = [];

    // Monthly Teacher Attendance Report Data
    public $selectedMonth; // 'Y-m', e.g. '2026-10'
    public $monthlyAttendanceMatrix = [];
    public $monthlyDays = [];
    public $monthlyStats = [
        'total_teachers' => 0,
        'working_days' => 0,
        'avg_attendance' => 0,
        'total_leaves' => 0,
        'total_absences' => 0,
        'total_substitutions' => 0,
    ];

    public function mount()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin' || $user->hasRole('Super Admin');

        if (!$isAdmin) {
            // For non-admins coming via teacher routes: enforce substitutions.manage
            if (request()->is('teacher/*')) {
                $this->authorize('substitutions.manage');
            } else {
                $this->authorize('schedule.manage');
            }
        }

        $this->selectedDate = now()->format('Y-m-d');
        $this->selectedMonth = now()->format('Y-m');
        
        $this->academicSessions = \App\Models\AcademicSession::orderBy('start_date', 'desc')->get();
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        $hasSessionAccess = $isAdmin ||
                            $user->can('substitutions.view-sessions') || 
                            $user->can('schedule.view-sessions');

        if (!$hasSessionAccess) {
            $this->selectedSessionId = $activeSessionId;
            $this->academicSessions = $this->academicSessions->where('id', $activeSessionId);
        } else {
            $this->selectedSessionId = $activeSessionId;
        }

        $this->loadData();
        if ($this->teachers->isNotEmpty() && !$this->selectedTeacherId) {
            $this->selectedTeacherId = $this->teachers->first()->id;
        }
        $this->loadMonthlyAttendanceData();
    }

    public function updatedSelectedSessionId()
    {
        $this->loadData();
        $this->loadMonthlyAttendanceData();
    }

    public function updatedSelectedDate()
    {
        $this->loadData();
    }

    public function updatedSelectedMonth()
    {
        $this->loadMonthlyAttendanceData();
    }

    public function updatedActiveTab($tab)
    {
        if ($tab === 'reports') {
            $this->loadMonthlyAttendanceData();
        }
    }

    public function updatedReportTab($subTab)
    {
        if ($subTab === 'monthly_attendance' || $subTab === 'teacher_report') {
            $this->loadMonthlyAttendanceData();
        }
    }

    public function updatedSelectedTeacherId()
    {
        if ($this->activeTab === 'reports') {
            $this->loadMonthlyAttendanceData();
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'reports') {
            $this->loadMonthlyAttendanceData();
        }
    }

    public function setReportTab($subTab)
    {
        $this->reportTab = $subTab;
        if ($subTab === 'monthly_attendance' || $subTab === 'teacher_report') {
            $this->loadMonthlyAttendanceData();
        }
    }

    public function selectTeacherForReport($teacherId)
    {
        $this->selectedTeacherId = $teacherId;
        $this->activeTab = 'reports';
        $this->reportTab = 'teacher_report';
        $this->loadMonthlyAttendanceData();
    }

    public function openArrangementForTeacher($teacherId)
    {
        $this->activeTab = 'arrangement';
        $this->focusedTeacherId = $teacherId;
    }

    public function getActiveShiftType(): string
    {
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        return ($shiftType === 'both') ? 'morning' : $shiftType;
    }

    public function loadTeacherAssignedSubs()
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        // Load assigned substitutions from dedicated substitutions table
        $subs = Substitution::with('class')
            ->where('academic_session_id', $this->selectedSessionId)
            ->whereDate('date', $selectedDate)
            ->where('shift_type', $shiftType)
            ->whereNotNull('substitute_teacher_id')
            ->orderBy('period_no')
            ->get();

        $this->teacherAssignedSubs = [];
        foreach ($subs as $sub) {
            $subTeacherId = $sub->substitute_teacher_id;
            if (!isset($this->teacherAssignedSubs[$subTeacherId])) {
                $this->teacherAssignedSubs[$subTeacherId] = [];
            }
            $this->teacherAssignedSubs[$subTeacherId][] = [
                'period_no' => $sub->period_no,
                'class_name' => $sub->class->name ?? 'Class',
            ];
        }

        // ── Daily workload counter from substitutions table ──
        $this->dailySubCounts = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->whereDate('date', $selectedDate)
            ->where('shift_type', $shiftType)
            ->whereNotNull('substitute_teacher_id')
            ->groupBy('substitute_teacher_id')
            ->select('substitute_teacher_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'substitute_teacher_id')
            ->toArray();

        // ── Monthly workload counter from substitutions table ──
        $monthDate = Carbon::parse($this->selectedDate);
        $this->monthlySubCounts = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereYear('date', $monthDate->year)
            ->whereMonth('date', $monthDate->month)
            ->whereNotNull('substitute_teacher_id')
            ->groupBy('substitute_teacher_id')
            ->select('substitute_teacher_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'substitute_teacher_id')
            ->toArray();
    }

    public function loadData()
    {
        $shiftType = $this->getActiveShiftType();

        $this->teachers = User::where('role', 'teacher')
            ->whereExists(function ($query) use ($shiftType) {
                $query->select(DB::raw(1))
                      ->from('session_user')
                      ->whereColumn('session_user.user_id', 'users.id')
                      ->where('session_user.academic_session_id', $this->selectedSessionId)
                      ->where('session_user.is_active', true)
                      ->when($shiftType !== 'regular', function ($q) use ($shiftType) {
                          $q->where(function ($sq) use ($shiftType) {
                              $sq->where('session_user.allowed_shifts', 'both')
                                 ->orWhere('session_user.allowed_shifts', $shiftType);
                          });
                      });
            })
            ->orderBy('name')
            ->get();
        
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        // Load attendances with remarks
        $attendances = TeacherAttendance::whereDate('date', $selectedDate)
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->get()->keyBy('teacher_id');
        
        $this->teacherStatuses = [];
        $this->teacherRemarks = [];
        $this->substitutions = [];
        $this->showAllTeachersToggle = [];
        $this->warningMessage = '';

        foreach ($this->teachers as $teacher) {
            $record = $attendances[$teacher->id] ?? null;
            $this->teacherStatuses[$teacher->id] = $record ? $record->status : 'Present';
            $this->teacherRemarks[$teacher->id]  = $record ? ($record->remarks ?? '') : '';
            
            // If absent/leave/official duty/short leave, load existing substitutions
            if ($this->teacherStatuses[$teacher->id] !== 'Present') {
                $this->loadExistingSubstitutions($teacher->id);
            }
        }

        $this->loadClosedClasses();
        $this->loadMergedClasses();
        $this->loadClassesForShift();
        $this->loadTeacherAssignedSubs();
    }

    public function updatedTeacherStatuses($value, $teacherId)
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        // Save status to DB immediately
        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $teacherId, 
                'date' => $selectedDate,
                'academic_session_id' => $this->selectedSessionId,
                'shift_type' => $shiftType,
            ],
            [
                'status' => $value,
                'remarks' => $this->teacherRemarks[$teacherId] ?? null
            ]
        );

        if ($value !== 'Present') {
            $this->loadExistingSubstitutions($teacherId);
        } else {
            // Remove from local state and clean up any substitutions
            unset($this->substitutions[$teacherId]);
            unset($this->showAllTeachersToggle[$teacherId]);
            $this->clearSubstitutionsForTeacher($teacherId);
        }
    }

    public function updatedTeacherRemarks($value, $teacherId)
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $teacherId, 
                'date' => $selectedDate,
                'academic_session_id' => $this->selectedSessionId,
                'shift_type' => $shiftType,
            ],
            [
                'status' => $this->teacherStatuses[$teacherId] ?? 'Present',
                'remarks' => $value
            ]
        );

        session()->flash('message', 'Remark saved.');
    }

    public function markAllPresent()
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        DB::beginTransaction();
        try {
            foreach ($this->teachers as $teacher) {
                TeacherAttendance::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id, 
                        'date' => $selectedDate,
                        'academic_session_id' => $this->selectedSessionId,
                        'shift_type' => $shiftType,
                    ],
                    [
                        'status' => 'Present',
                    ]
                );
                $this->teacherStatuses[$teacher->id] = 'Present';
                $this->clearSubstitutionsForTeacher($teacher->id);
            }
            DB::commit();
            $this->loadData();
            session()->flash('message', 'All teachers marked present successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error marking all present: ' . $e->getMessage());
        }
    }

    public function loadClassesForShift()
    {
        $shiftType = $this->getActiveShiftType();
        $this->classesForCurrentShift = Classes::where('academic_session_id', $this->selectedSessionId)
            ->when($shiftType !== 'regular', function ($q) use ($shiftType) {
                $q->where('shift_type', $shiftType);
            })
            ->orderBy('numeric_value')
            ->orderBy('name')
            ->get();
    }

    public function loadClosedClasses()
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        $this->closedClassesList = ClosedClassroom::with('class')
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'class_id' => $c->class_id,
                    'class_name' => $c->class?->name ?? 'Class',
                    'reason' => $c->reason ?? 'Closed',
                ];
            })
            ->toArray();
    }

    public function loadMergedClasses()
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        $merges = DailyClassMerge::with(['sourceClass', 'targetClass'])
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->get();

        $grouped = [];
        foreach ($merges as $m) {
            $key = $m->source_class_id . '_' . $m->target_class_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'source_class_id' => $m->source_class_id,
                    'target_class_id' => $m->target_class_id,
                    'source_class_name' => $m->sourceClass?->name ?? 'Class',
                    'target_class_name' => $m->targetClass?->name ?? 'Class',
                    'periods_count' => 0,
                ];
            }
            $grouped[$key]['periods_count']++;
        }

        $this->mergedClassesList = array_values($grouped);
    }

    public function openCloseClassModal()
    {
        $this->loadClassesForShift();
        $this->loadClosedClasses();
        $this->reset(['closeClassId', 'closeClassReason']);
        $this->showCloseClassModal = true;
    }

    public function closeCloseClassModal()
    {
        $this->showCloseClassModal = false;
        $this->reset(['closeClassId', 'closeClassReason']);
    }

    public function saveCloseClass()
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        if (empty($this->closeClassId)) {
            $this->addError('closeClassId', 'Please select a class or group to close.');
            return;
        }

        $this->loadClassesForShift();
        $targetClassIds = [];

        // Handle bulk options
        if ($this->closeClassId === 'school_all') {
            foreach ($this->classesForCurrentShift as $c) {
                $grade = (int) $c->numeric_value;
                if ($grade >= 6 && $grade <= 10) {
                    $targetClassIds[] = $c->id;
                }
            }
        } elseif ($this->closeClassId === 'college_all') {
            foreach ($this->classesForCurrentShift as $c) {
                $grade = (int) $c->numeric_value;
                if ($grade >= 11 && $grade <= 12) {
                    $targetClassIds[] = $c->id;
                }
            }
        } elseif (preg_match('/^grade_all_(\d+)$/', $this->closeClassId, $matches)) {
            $targetGrade = (int) $matches[1];
            foreach ($this->classesForCurrentShift as $c) {
                if ((int) $c->numeric_value === $targetGrade) {
                    $targetClassIds[] = $c->id;
                }
            }
        } else {
            $targetClassIds[] = (int) $this->closeClassId;
        }

        if (empty($targetClassIds)) {
            $this->addError('closeClassId', 'No matching classes found in current session and shift.');
            return;
        }

        foreach ($targetClassIds as $cId) {
            ClosedClassroom::updateOrCreate(
                [
                    'academic_session_id' => $this->selectedSessionId,
                    'shift_type' => $shiftType,
                    'date' => $selectedDate,
                    'class_id' => $cId,
                ],
                [
                    'reason' => $this->closeClassReason ?: 'Closed for today',
                ]
            );
        }

        $this->loadClosedClasses();
        $this->loadTeacherAssignedSubs();
        $this->reset(['closeClassId', 'closeClassReason']);
        session()->flash('message', 'Class(es) closed successfully for today.');
    }

    public function deleteClosedClass($id)
    {
        ClosedClassroom::destroy($id);
        $this->loadClosedClasses();
        $this->loadTeacherAssignedSubs();
        session()->flash('message', 'Class re-opened successfully.');
    }

    public function openMergeModal()
    {
        $this->loadClassesForShift();
        $this->loadMergedClasses();
        $this->reset(['mergeSourceClassId', 'mergeTargetClassId']);
        $this->showMergeModal = true;
    }

    public function closeMergeModal()
    {
        $this->showMergeModal = false;
        $this->reset(['mergeSourceClassId', 'mergeTargetClassId']);
    }

    public function saveMergeClass()
    {
        $this->validate([
            'mergeSourceClassId' => 'required|different:mergeTargetClassId',
            'mergeTargetClassId' => 'required',
        ]);

        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');

        // Fetch source class timetables for today in active session & shift
        $sourceTimetables = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.class_id', $this->mergeSourceClassId)
            ->where('timetables.day', $dayOfWeek)
            ->when($shiftType !== 'both', fn($q) => $q->where('classes.shift_type', $shiftType))
            ->select('timetables.*')
            ->get();

        $mergesCount = 0;
        foreach ($sourceTimetables as $src) {
            $targetTimetable = DB::table('timetables')
                ->join('classes', 'timetables.class_id', '=', 'classes.id')
                ->where('classes.academic_session_id', $this->selectedSessionId)
                ->where('timetables.class_id', $this->mergeTargetClassId)
                ->where('timetables.period_no', $src->period_no)
                ->where('timetables.day', $dayOfWeek)
                ->when($shiftType !== 'both', fn($q) => $q->where('classes.shift_type', $shiftType))
                ->select('timetables.*')
                ->first();

            if ($targetTimetable) {
                DailyClassMerge::updateOrCreate(
                    [
                        'date' => $selectedDate,
                        'source_timetable_id' => $src->id,
                    ],
                    [
                        'academic_session_id' => $this->selectedSessionId,
                        'shift_type' => $shiftType,
                        'source_class_id' => $this->mergeSourceClassId,
                        'target_class_id' => $this->mergeTargetClassId,
                        'target_timetable_id' => $targetTimetable->id,
                    ]
                );
                $mergesCount++;
            }
        }

        if ($mergesCount === 0) {
            $this->addError('mergeSourceClassId', 'No matching timetable periods found to merge between these two classes on ' . $dayOfWeek . '.');
            return;
        }

        $this->loadMergedClasses();
        $this->loadTeacherAssignedSubs();
        $this->reset(['mergeSourceClassId', 'mergeTargetClassId']);
        session()->flash('message', "Successfully merged {$mergesCount} period(s) for today.");
    }

    public function unmergeClasses($sourceClassId, $targetClassId)
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        DailyClassMerge::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->where('source_class_id', $sourceClassId)
            ->where('target_class_id', $targetClassId)
            ->delete();

        $this->loadMergedClasses();
        $this->loadTeacherAssignedSubs();
        session()->flash('message', 'Classes unmerged successfully.');
    }

    public function clearSubstitutionsForTeacher($teacherId)
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');
        $dayOfWeek = Carbon::parse($selectedDate)->format('l');

        // Clean up any timetable substitute records for this teacher's scheduled periods
        $regularClasses = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.is_substitute', false)
            ->select('timetables.*')
            ->get();

        foreach ($regularClasses as $regClass) {
            DB::table('timetables')
                ->where('class_id', $regClass->class_id)
                ->where('period_no', $regClass->period_no)
                ->where('is_substitute', true)
                ->where('substitute_date', $selectedDate)
                ->delete();
        }

        Substitution::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->where('absent_teacher_id', $teacherId)
            ->delete();

        $this->loadTeacherAssignedSubs();
    }

    public function loadExistingSubstitutions($teacherId)
    {
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');
        $shiftType = $this->getActiveShiftType();

        // Fetch regular schedule for this teacher
        $regularSchedule = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select('timetables.*')
            ->get();

        if (!isset($this->substitutions[$teacherId])) {
            $this->substitutions[$teacherId] = [];
            $this->showAllTeachersToggle[$teacherId] = [];
        }

        foreach ($regularSchedule as $schedule) {
            // Check existing row in substitutions table
            $existingSub = Substitution::where('academic_session_id', $this->selectedSessionId)
                ->where('shift_type', $shiftType)
                ->where('class_id', $schedule->class_id)
                ->where('period_no', $schedule->period_no)
                ->whereDate('date', $selectedDate)
                ->first();

            $this->substitutions[$teacherId][$schedule->period_no] = $existingSub ? $existingSub->substitute_teacher_id : '';
            if (!isset($this->showAllTeachersToggle[$teacherId][$schedule->period_no])) {
                $this->showAllTeachersToggle[$teacherId][$schedule->period_no] = false;
            }
        }
    }

    public function assignSubstitute($absentTeacherId, $periodNo, $classId, $subjectId, $timetableId = null)
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');
        $substituteTeacherId = $this->substitutions[$absentTeacherId][$periodNo] ?? null;
        $this->activeTab = 'arrangement';
        $this->warningMessage = '';

        if (!$substituteTeacherId) {
            // Remove substitution from dedicated substitutions table
            Substitution::where('academic_session_id', $this->selectedSessionId)
                ->where('shift_type', $shiftType)
                ->where('class_id', $classId)
                ->where('period_no', $periodNo)
                ->whereDate('date', $selectedDate)
                ->delete();

            // Also clean up legacy timetables table for schedule queries
            DB::table('timetables')
                ->where('class_id', $classId)
                ->where('period_no', $periodNo)
                ->where('is_substitute', true)
                ->where('substitute_date', $selectedDate)
                ->delete();
            
            $this->substitutions[$absentTeacherId][$periodNo] = '';
            $this->loadTeacherAssignedSubs();
            return;
        }

        // Logic Check: Is the selected substitute already busy?
        $isBusy = $this->checkIfTeacherIsBusy($substituteTeacherId, $periodNo, $classId);
        if ($isBusy) {
            $subTeacherName = collect($this->teachers)->firstWhere('id', $substituteTeacherId)->name ?? 'Teacher';
            $this->warningMessage = "Notice: {$subTeacherName} is already assigned during Period {$periodNo}. Override assignment applied.";
        }

        // Link with attendance record if available
        $attendanceRecord = TeacherAttendance::where('teacher_id', $absentTeacherId)
            ->whereDate('date', $selectedDate)
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->first();

        // Create or update in dedicated substitutions table
        Substitution::updateOrCreate(
            [
                'academic_session_id' => $this->selectedSessionId,
                'shift_type' => $shiftType,
                'date' => $selectedDate,
                'class_id' => $classId,
                'period_no' => $periodNo,
            ],
            [
                'subject_id' => $subjectId,
                'timetable_id' => $timetableId,
                'absent_teacher_id' => $absentTeacherId,
                'substitute_teacher_id' => $substituteTeacherId,
                'teacher_attendance_id' => $attendanceRecord?->id,
                'status' => 'assigned',
                'created_by' => auth()->id(),
            ]
        );

        // Also sync to timetables table for schedule queries & backward compatibility
        DB::table('timetables')
            ->where('class_id', $classId)
            ->where('period_no', $periodNo)
            ->where('is_substitute', true)
            ->where('substitute_date', $selectedDate)
            ->delete();

        DB::table('timetables')->insert([
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $substituteTeacherId,
            'day' => Carbon::parse($selectedDate)->format('l'),
            'period_no' => $periodNo,
            'room' => '',
            'is_divided' => false,
            'is_substitute' => true,
            'substitute_date' => $selectedDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->substitutions[$absentTeacherId][$periodNo] = $substituteTeacherId;
        $this->loadTeacherAssignedSubs();
        session()->flash('message', 'Substitute assigned successfully.');
    }

    public function checkIfTeacherIsBusy($teacherId, $periodNo, $classId = null)
    {
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        // Classrooms that are closed today do not count as busy
        $closedClassIds = ClosedClassroom::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->pluck('class_id')
            ->toArray();

        // Source classrooms in class merges are merged into target, so source timetable periods are freed up
        $mergedSourceTimetableIds = DailyClassMerge::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->pluck('source_timetable_id')
            ->toArray();

        // 1. Check master weekly timetable
        $hasRegular = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.period_no', $periodNo)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->when($classId, function($q) use ($classId) {
                return $q->where('timetables.class_id', '!=', $classId);
            })
            ->when(!empty($closedClassIds), function($q) use ($closedClassIds) {
                return $q->whereNotIn('timetables.class_id', $closedClassIds);
            })
            ->when(!empty($mergedSourceTimetableIds), function($q) use ($mergedSourceTimetableIds) {
                return $q->whereNotIn('timetables.id', $mergedSourceTimetableIds);
            })
            ->exists();

        if ($hasRegular) return true;

        // 2. Check other substitutions for today
        $hasSubstitute = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->whereDate('date', $selectedDate)
            ->where('shift_type', $shiftType)
            ->where('period_no', $periodNo)
            ->where('substitute_teacher_id', $teacherId)
            ->exists();

        return $hasSubstitute;
    }

    public function getAvailableTeachersForPeriod($periodNo, $currentlyAssignedId = null, $classId = null)
    {
        $busyTeacherIds = [];
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        // Fetch closed class IDs for today
        $closedClassIds = ClosedClassroom::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->pluck('class_id')
            ->toArray();

        // Fetch merged source timetable IDs for today
        $mergedSourceTimetableIds = DailyClassMerge::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->pluck('source_timetable_id')
            ->toArray();

        // 1. Teachers with regular classes (excluding closed classes and merged source classes)
        $regularBusy = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.period_no', $periodNo)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->when($classId, function($q) use ($classId) {
                return $q->where('timetables.class_id', '!=', $classId);
            })
            ->when(!empty($closedClassIds), function($q) use ($closedClassIds) {
                return $q->whereNotIn('timetables.class_id', $closedClassIds);
            })
            ->when(!empty($mergedSourceTimetableIds), function($q) use ($mergedSourceTimetableIds) {
                return $q->whereNotIn('timetables.id', $mergedSourceTimetableIds);
            })
            ->pluck('timetables.teacher_id')
            ->toArray();
        
        $busyTeacherIds = array_merge($busyTeacherIds, $regularBusy);

        // 2. Teachers already assigned as substitutes in substitutions table
        $subBusy = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->whereDate('date', $selectedDate)
            ->where('shift_type', $shiftType)
            ->where('period_no', $periodNo)
            ->whereNotNull('substitute_teacher_id')
            ->pluck('substitute_teacher_id')
            ->toArray();

        $busyTeacherIds = array_merge($busyTeacherIds, $subBusy);

        // 3. Teachers who are Absent or on Leave today
        $absentTeacherIds = [];
        foreach ($this->teacherStatuses as $tId => $status) {
            if ($status === 'Absent' || $status === 'Leave') {
                $absentTeacherIds[] = $tId;
            }
        }
        $busyTeacherIds = array_merge($busyTeacherIds, $absentTeacherIds);
        $busyTeacherIds = array_unique($busyTeacherIds);

        // If currently assigned teacher is provided, keep them selectable
        if ($currentlyAssignedId !== null) {
            $busyTeacherIds = array_diff($busyTeacherIds, [$currentlyAssignedId]);
        }

        return collect($this->teachers)->filter(function($t) use ($busyTeacherIds) {
            return !in_array($t->id, $busyTeacherIds);
        })->values();
    }

    public function getTeacherSchedule($teacherId)
    {
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

        $closedClassrooms = ClosedClassroom::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->get()
            ->keyBy('class_id');

        $dailyMerges = DailyClassMerge::with('targetClass')
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', $selectedDate)
            ->get()
            ->keyBy('source_timetable_id');

        $periods = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select('timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
            ->orderBy('timetables.period_no')
            ->get();

        foreach ($periods as $p) {
            $p->is_closed = isset($closedClassrooms[$p->class_id]);
            $p->closed_reason = $p->is_closed ? ($closedClassrooms[$p->class_id]->reason ?: 'Class Closed') : null;

            $merge = $dailyMerges[$p->id] ?? null;
            $p->is_merged_away = !empty($merge);
            $p->merged_with_class_name = $p->is_merged_away ? ($merge->targetClass?->name ?? 'Combined Class') : null;
        }

        return $periods;
    }

    public function loadMonthlyAttendanceData()
    {
        $shiftType = $this->getActiveShiftType();
        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        $weekendMode = Setting::get('weekend_mode', 'sat_sun');

        // Fetch session holidays
        $holidays = Holiday::where('academic_session_id', $this->selectedSessionId)
            ->where('start_date', '<=', $endOfMonth->format('Y-m-d'))
            ->where('end_date', '>=', $startOfMonth->format('Y-m-d'))
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where(function ($sq) use ($shiftType) {
                    $sq->whereNull('shift_type')
                       ->orWhere('shift_type', $shiftType);
                });
            })
            ->get();

        // Build days metadata
        $this->monthlyDays = [];
        $workingDaysCount = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currDate = $startOfMonth->copy()->day($day);
            $currDateStr = $currDate->format('Y-m-d');

            $isWeekend = ($weekendMode === 'sun_only') 
                ? $currDate->isSunday() 
                : $currDate->isWeekend();

            $isHoliday = $holidays->contains(function ($h) use ($currDateStr) {
                return $currDateStr >= $h->start_date->format('Y-m-d') && $currDateStr <= $h->end_date->format('Y-m-d');
            });

            if (!$isWeekend && !$isHoliday) {
                $workingDaysCount++;
            }

            $this->monthlyDays[] = [
                'day' => $day,
                'date' => $currDateStr,
                'day_name' => $currDate->format('D'),
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
            ];
        }

        // Fetch all attendance records for this month (safe SQLite date comparison)
        $attendances = TeacherAttendance::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', '>=', $startOfMonth->format('Y-m-d'))
            ->whereDate('date', '<=', $endOfMonth->format('Y-m-d'))
            ->get()
            ->groupBy('teacher_id');

        // Fetch monthly substitutions count per substitute teacher
        $substitutionsCount = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', '>=', $startOfMonth->format('Y-m-d'))
            ->whereDate('date', '<=', $endOfMonth->format('Y-m-d'))
            ->whereNotNull('substitute_teacher_id')
            ->groupBy('substitute_teacher_id')
            ->select('substitute_teacher_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'substitute_teacher_id')
            ->toArray();

        $this->monthlyAttendanceMatrix = [];
        $totalPresentAll = 0;
        $totalLeavesAll = 0;
        $totalAbsencesAll = 0;

        foreach ($this->teachers as $teacher) {
            $teacherRecords = $attendances[$teacher->id] ?? collect();
            $recordsByDate = $teacherRecords->keyBy(fn($r) => Carbon::parse($r->date)->day);

            $pCount = 0;
            $lCount = 0;
            $slCount = 0;
            $odCount = 0;
            $aCount = 0;
            $dayCells = [];

            foreach ($this->monthlyDays as $dayInfo) {
                $dayNum = $dayInfo['day'];
                $rec = $recordsByDate[$dayNum] ?? null;

                if ($dayInfo['is_weekend']) {
                    $code = 'W';
                } elseif ($dayInfo['is_holiday']) {
                    $code = 'H';
                } elseif ($rec) {
                    $raw = strtolower(str_replace(' ', '_', $rec->status));
                    if ($raw === 'present') {
                        $code = 'P';
                        $pCount++;
                    } elseif ($raw === 'leave') {
                        $code = 'L';
                        $lCount++;
                    } elseif ($raw === 'short_leave') {
                        $code = 'SL';
                        $slCount++;
                    } elseif ($raw === 'official_duty') {
                        $code = 'OD';
                        $odCount++;
                    } elseif ($raw === 'absent') {
                        $code = 'A';
                        $aCount++;
                    } else {
                        $code = 'P';
                        $pCount++;
                    }
                } else {
                    $code = '-'; // Not marked yet
                }

                $dayCells[$dayNum] = [
                    'code' => $code,
                    'status' => $code,
                    'remarks' => $rec->remarks ?? '',
                ];
            }

            // Attendance percentage formula: (Present + OD + (SL * 0.5)) / max(1, workingDays) * 100
            $effectivePresent = $pCount + $odCount + ($slCount * 0.5);
            $percentage = $workingDaysCount > 0 ? round(($effectivePresent / $workingDaysCount) * 100, 1) : 0;
            if ($percentage > 100) $percentage = 100;

            $totalPresentAll += $pCount;
            $totalLeavesAll += ($lCount + $slCount);
            $totalAbsencesAll += $aCount;

            $this->monthlyAttendanceMatrix[] = [
                'teacher' => $teacher,
                'teacher_id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'present' => $pCount,
                'present_count' => $pCount,
                'leave' => $lCount,
                'leave_count' => $lCount,
                'short_leave' => $slCount,
                'short_leave_count' => $slCount,
                'official_duty' => $odCount,
                'duty_count' => $odCount,
                'absent' => $aCount,
                'absent_count' => $aCount,
                'percentage' => $percentage,
                'substitutions' => $substitutionsCount[$teacher->id] ?? 0,
                'substitutions_taken' => $substitutionsCount[$teacher->id] ?? 0,
                'days' => $dayCells,
            ];
        }

        $teacherCount = count($this->teachers);
        $avgAttendance = $teacherCount > 0 
            ? round(collect($this->monthlyAttendanceMatrix)->avg('percentage'), 1) 
            : 0;

        $this->monthlyStats = [
            'total_teachers' => $teacherCount,
            'working_days' => $workingDaysCount,
            'avg_attendance' => $avgAttendance,
            'total_leaves' => $totalLeavesAll,
            'total_absences' => $totalAbsencesAll,
            'total_substitutions' => array_sum($substitutionsCount),
        ];
    }

    public function getSelectedTeacherMonthlyDetails(): ?array
    {
        if (!$this->selectedTeacherId) {
            $this->selectedTeacherId = collect($this->teachers)->first()?->id;
        }

        if (!$this->selectedTeacherId) {
            return null;
        }

        $teacher = collect($this->teachers)->firstWhere('id', $this->selectedTeacherId);
        if (!$teacher) {
            $teacher = User::find($this->selectedTeacherId);
        }
        if (!$teacher) {
            return null;
        }

        $shiftType = $this->getActiveShiftType();
        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 1. Fetch attendance records for this teacher in this month
        $attendances = TeacherAttendance::where('teacher_id', $this->selectedTeacherId)
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('date', '>=', $startOfMonth->format('Y-m-d'))
            ->whereDate('date', '<=', $endOfMonth->format('Y-m-d'))
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->date)->format('Y-m-d'));

        // 2. Fetch substitution duties performed by this teacher in this month
        $substitutions = Substitution::with(['class', 'subject', 'absentTeacher'])
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->where('substitute_teacher_id', $this->selectedTeacherId)
            ->whereDate('date', '>=', $startOfMonth->format('Y-m-d'))
            ->whereDate('date', '<=', $endOfMonth->format('Y-m-d'))
            ->get()
            ->groupBy(fn($s) => Carbon::parse($s->date)->format('Y-m-d'));

        $pCount = 0;
        $lCount = 0;
        $slCount = 0;
        $odCount = 0;
        $aCount = 0;
        $totalSubDuties = 0;
        $daysList = [];

        foreach ($this->monthlyDays as $dayInfo) {
            $currDateStr = $dayInfo['date'];
            $rec = $attendances[$currDateStr] ?? null;
            $subsOnDay = $substitutions[$currDateStr] ?? collect();
            $totalSubDuties += $subsOnDay->count();

            $statusText = 'Unmarked';
            $statusCode = '-';
            $remarks = $rec?->remarks ?? '';

            if ($this->excludeWeekends && $dayInfo['is_weekend']) {
                continue;
            }

            if ($dayInfo['is_weekend']) {
                $statusText = 'Weekend';
                $statusCode = 'W';
            } elseif ($dayInfo['is_holiday']) {
                $statusText = 'Holiday';
                $statusCode = 'H';
            } elseif ($rec) {
                $statusText = $rec->status;
                $raw = strtolower(str_replace(' ', '_', $rec->status));
                if ($raw === 'present') {
                    $statusCode = 'P';
                    $pCount++;
                } elseif ($raw === 'leave') {
                    $statusCode = 'L';
                    $lCount++;
                } elseif ($raw === 'short_leave') {
                    $statusCode = 'SL';
                    $slCount++;
                } elseif ($raw === 'official_duty') {
                    $statusCode = 'OD';
                    $odCount++;
                } elseif ($raw === 'absent') {
                    $statusCode = 'A';
                    $aCount++;
                } else {
                    $statusCode = 'P';
                    $pCount++;
                }
            }

            $dutiesFormatted = [];
            foreach ($subsOnDay as $sub) {
                $dutiesFormatted[] = [
                    'period_no' => $sub->period_no,
                    'class_name' => $sub->class?->name ?? 'Class',
                    'subject_name' => $sub->subject?->name ?? 'Subject',
                    'absent_teacher_name' => $sub->absentTeacher?->name ?? 'Absent Teacher',
                ];
            }

            $daysList[] = [
                'day' => $dayInfo['day'],
                'date' => $currDateStr,
                'formatted_date' => Carbon::parse($currDateStr)->format('D, M j, Y'),
                'day_name' => $dayInfo['day_name'],
                'is_weekend' => $dayInfo['is_weekend'],
                'is_holiday' => $dayInfo['is_holiday'],
                'status' => $statusText,
                'code' => $statusCode,
                'remarks' => $remarks,
                'substitutions' => $dutiesFormatted,
            ];
        }

        $workingDaysCount = $this->monthlyStats['working_days'] ?? 0;
        $effectivePresent = $pCount + $odCount + ($slCount * 0.5);
        $percentage = $workingDaysCount > 0 ? round(($effectivePresent / $workingDaysCount) * 100, 1) : 0;
        if ($percentage > 100) $percentage = 100;

        return [
            'teacher' => $teacher,
            'summary' => [
                'present' => $pCount,
                'leave' => $lCount,
                'short_leave' => $slCount,
                'official_duty' => $odCount,
                'absent' => $aCount,
                'substitutions' => $totalSubDuties,
                'percentage' => $percentage,
                'working_days' => $workingDaysCount,
            ],
            'days' => $daysList,
        ];
    }

    public function getTeacherPrintUrl($teacherId = null)
    {
        $tId = $teacherId ?: $this->selectedTeacherId;
        $routeName = request()->is('teacher/*') 
            ? 'teacher.shared.substitutions.teacher_attendance.print' 
            : 'admin.substitutions.teacher_attendance.print';

        return route($routeName, [
            'teacher_id' => $tId,
            'month' => $this->selectedMonth,
            'session_id' => $this->selectedSessionId,
            'exclude_weekends' => $this->excludeWeekends ? 1 : 0,
        ]);
    }

    public function getPrintUrl($autoDownload = true)
    {
        $routeName = request()->is('teacher/*') 
            ? 'teacher.shared.substitutions.print' 
            : 'admin.substitutions.print';

        return route($routeName, [
            'date' => $this->selectedDate,
            'session_id' => $this->selectedSessionId,
            'auto_download' => $autoDownload ? 1 : 0
        ]);
    }

    public function getMonthlyPrintUrl()
    {
        $routeName = request()->is('teacher/*') 
            ? 'teacher.shared.substitutions.monthly_attendance.print' 
            : 'admin.substitutions.monthly_attendance.print';

        return route($routeName, [
            'month' => $this->selectedMonth,
            'session_id' => $this->selectedSessionId
        ]);
    }

    public function prepareReportData()
    {
        $reportData = [];
        
        foreach ($this->teachers as $teacher) {
            $status = $this->teacherStatuses[$teacher->id] ?? 'Present';
            
            if ($status === 'Present') continue;

            $schedule = $this->getTeacherSchedule($teacher->id);
            if ($schedule->isEmpty()) continue;

            $teacherPeriods = [];
            
            foreach ($schedule as $period) {
                $substituteId = $this->substitutions[$teacher->id][$period->period_no] ?? null;

                if (!empty($period->is_closed)) {
                    $substituteName = 'Class Closed (' . ($period->closed_reason ?: 'Closed for today') . ')';
                } elseif (!empty($period->is_merged_away)) {
                    $substituteName = 'Merged with ' . $period->merged_with_class_name;
                } else {
                    $substituteName = $substituteId ? (collect($this->teachers)->firstWhere('id', $substituteId)->name ?? 'Unknown') : 'Unassigned';
                }

                // Official Duty logic: Only include assigned periods if not closed/merged
                if ($status === 'Official Duty' && !$substituteId && empty($period->is_closed) && empty($period->is_merged_away)) {
                    continue;
                }

                $teacherPeriods[] = [
                    'period_no' => $period->period_no,
                    'class_name' => $period->class_name,
                    'subject_name' => $period->subject_name,
                    'substitute_name' => $substituteName,
                    'is_closed' => !empty($period->is_closed),
                    'is_merged_away' => !empty($period->is_merged_away),
                ];
            }

            if (!empty($teacherPeriods)) {
                $reportData[] = [
                    'teacher_name' => $teacher->name,
                    'status' => $status,
                    'remarks' => $this->teacherRemarks[$teacher->id] ?? '',
                    'periods' => $teacherPeriods
                ];
            }
        }

        return $reportData;
    }

    public function render()
    {
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.substitution-manager')
            ->layout($layout, ['title' => 'Teacher Attendance & Substitution Manager']);
    }
}
