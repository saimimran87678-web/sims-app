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
use Carbon\Carbon;

class SubstitutionManager extends Component
{
    // Tab Navigation: 'attendance', 'arrangement', 'reports'
    public $activeTab = 'attendance';
    // Report Sub-Tab: 'monthly_attendance', 'workload'
    public $reportTab = 'monthly_attendance';

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
    }

    public function updatedSelectedSessionId()
    {
        $this->loadData();
        if ($this->activeTab === 'reports') {
            $this->loadMonthlyAttendanceData();
        }
    }

    public function updatedSelectedDate()
    {
        $this->loadData();
    }

    public function updatedSelectedMonth()
    {
        $this->loadMonthlyAttendanceData();
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
        if ($subTab === 'monthly_attendance') {
            $this->loadMonthlyAttendanceData();
        }
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
                'absent_teacher_id' => $sub->absent_teacher_id,
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

    public function clearSubstitutionsForTeacher($teacherId)
    {
        $shiftType = $this->getActiveShiftType();
        $selectedDate = Carbon::parse($this->selectedDate)->format('Y-m-d');

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
        $this->warningMessage = '';

        if (!$substituteTeacherId) {
            // Remove substitution from dedicated substitutions table
            Substitution::where('academic_session_id', $this->selectedSessionId)
                ->where('shift_type', $shiftType)
                ->where('class_id', $classId)
                ->where('period_no', $periodNo)
                ->whereDate('date', $selectedDate)
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

        $this->substitutions[$absentTeacherId][$periodNo] = $substituteTeacherId;
        $this->loadTeacherAssignedSubs();
        session()->flash('message', 'Substitute assigned successfully.');
    }

    public function checkIfTeacherIsBusy($teacherId, $periodNo, $classId = null)
    {
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');
        $shiftType = $this->getActiveShiftType();

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
            ->exists();

        if ($hasRegular) return true;

        // 2. Check other substitutions for today
        $hasSubstitute = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->where('date', $this->selectedDate)
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

        // 1. Teachers with regular classes
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
            ->pluck('timetables.teacher_id')
            ->toArray();
        
        $busyTeacherIds = array_merge($busyTeacherIds, $regularBusy);

        // 2. Teachers already assigned as substitutes in substitutions table
        $subBusy = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->where('date', $this->selectedDate)
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

        return DB::table('timetables')
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

        // Fetch all attendance records for this month
        $attendances = TeacherAttendance::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->groupBy('teacher_id');

        // Fetch monthly substitutions count per substitute teacher
        $substitutionsCount = Substitution::where('academic_session_id', $this->selectedSessionId)
            ->where('shift_type', $shiftType)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
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
                'teacher_id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'present' => $pCount,
                'leave' => $lCount,
                'short_leave' => $slCount,
                'official_duty' => $odCount,
                'absent' => $aCount,
                'percentage' => $percentage,
                'substitutions' => $substitutionsCount[$teacher->id] ?? 0,
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

    public function getPrintUrl()
    {
        $routeName = request()->is('teacher/*') 
            ? 'teacher.shared.substitutions.print' 
            : 'admin.substitutions.print';

        return route($routeName, [
            'date' => $this->selectedDate,
            'session_id' => $this->selectedSessionId
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
                $substituteName = $substituteId ? collect($this->teachers)->firstWhere('id', $substituteId)->name ?? 'Unknown' : 'Unassigned';

                // Official Duty logic: Only include assigned periods
                if ($status === 'Official Duty' && !$substituteId) {
                    continue;
                }

                $teacherPeriods[] = [
                    'period_no' => $period->period_no,
                    'class_name' => $period->class_name,
                    'subject_name' => $period->subject_name,
                    'substitute_name' => $substituteName,
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
