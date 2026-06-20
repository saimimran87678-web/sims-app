<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\User;
use App\Models\TeacherAttendance;
use Carbon\Carbon;


class SubstitutionManager extends Component
{
    public $selectedDate;
    public $selectedSessionId;
    public $academicSessions = [];
    
    public $teachers = [];
    public $teacherStatuses = []; // [teacher_id => status]
    
    // Structure: [teacher_id => [period_no => substitute_teacher_id]]
    public $substitutions = [];
    
    // Toggles for "Show All Teachers" per period assignment
    // Structure: [teacher_id => [period_no => boolean]]
    public $showAllTeachersToggle = [];

    // Notifications
    public $warningMessage = '';

    public function mount()
    {
        $this->authorize('schedule.manage');

        $this->selectedDate = now()->format('Y-m-d');
        
        $this->academicSessions = \App\Models\AcademicSession::orderBy('start_date', 'desc')->get();
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        if (!auth()->user()->can('schedule.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
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
    }

    public function updatedSelectedDate()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->teachers = User::where('role', 'teacher')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('session_user')
                      ->whereColumn('session_user.user_id', 'users.id')
                      ->where('session_user.academic_session_id', $this->selectedSessionId)
                      ->where('session_user.is_active', true);
            })
            ->orderBy('name')
            ->get();
        
        // Load attendances for the selected date and session
        $attendances = TeacherAttendance::where('date', $this->selectedDate)
            ->where('academic_session_id', $this->selectedSessionId)
            ->get()->keyBy('teacher_id');
        
        $this->teacherStatuses = [];
        $this->substitutions = [];
        $this->showAllTeachersToggle = [];
        $this->warningMessage = '';

        foreach ($this->teachers as $teacher) {
            $this->teacherStatuses[$teacher->id] = $attendances[$teacher->id]->status ?? 'Present';
            
            // If absent/leave/official duty, load their existing substitutions for this date
            if ($this->teacherStatuses[$teacher->id] !== 'Present') {
                $this->loadExistingSubstitutions($teacher->id);
            }
        }
    }

    public function updatedTeacherStatuses($value, $teacherId)
    {
        // Save to DB immediately
        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $teacherId, 
                'date' => $this->selectedDate,
                'academic_session_id' => $this->selectedSessionId
            ],
            ['status' => $value]
        );

        if ($value !== 'Present') {
            $this->loadExistingSubstitutions($teacherId);
        } else {
            // Remove from local state
            unset($this->substitutions[$teacherId]);
            unset($this->showAllTeachersToggle[$teacherId]);
            // Also optionally delete existing substitutions from DB? 
            // If they are marked Present, we should remove any substitute assignments for them on this day.
            $this->clearSubstitutionsForTeacher($teacherId);
        }
    }

    public function clearSubstitutionsForTeacher($teacherId)
    {
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');

        // Find regular classes for this teacher
        $regularClasses = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.is_substitute', false)
            ->get();

        foreach ($regularClasses as $regClass) {
            DB::table('timetables')
                ->where('class_id', $regClass->class_id)
                ->where('period_no', $regClass->period_no)
                ->where('is_substitute', true)
                ->where('substitute_date', $this->selectedDate)
                ->delete();
        }
    }

    public function loadExistingSubstitutions($teacherId)
    {
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');

        // Fetch regular schedule for this teacher
        $regularSchedule = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.is_substitute', false)
            ->get();

        if (!isset($this->substitutions[$teacherId])) {
            $this->substitutions[$teacherId] = [];
            $this->showAllTeachersToggle[$teacherId] = [];
        }

        foreach ($regularSchedule as $schedule) {
            // Check if there's a substitute already assigned for this class and period on this date
            $existingSub = DB::table('timetables')
                ->where('class_id', $schedule->class_id)
                ->where('period_no', $schedule->period_no)
                ->where('is_substitute', true)
                ->where('substitute_date', $this->selectedDate)
                ->first();

            $this->substitutions[$teacherId][$schedule->period_no] = $existingSub ? $existingSub->teacher_id : '';
            if (!isset($this->showAllTeachersToggle[$teacherId][$schedule->period_no])) {
                $this->showAllTeachersToggle[$teacherId][$schedule->period_no] = false;
            }
        }
    }

    public function assignSubstitute($absentTeacherId, $periodNo, $classId, $subjectId)
    {
        $substituteTeacherId = $this->substitutions[$absentTeacherId][$periodNo] ?? null;
        
        \Log::info("assignSubstitute called", [
            'absent' => $absentTeacherId,
            'period' => $periodNo,
            'class' => $classId,
            'subject' => $subjectId,
            'sub' => $substituteTeacherId
        ]);
        $this->warningMessage = '';

        if (!$substituteTeacherId) {
            // Remove substitution
            DB::table('timetables')
                ->where('class_id', $classId)
                ->where('period_no', $periodNo)
                ->where('is_substitute', true)
                ->where('substitute_date', $this->selectedDate)
                ->delete();
            
            $this->substitutions[$absentTeacherId][$periodNo] = '';
            return;
        }

        // Logic Check: Is double substitution happening?
        // Prevent another substitute from being assigned to the same class/period
        $existingSub = DB::table('timetables')
            ->where('class_id', $classId)
            ->where('period_no', $periodNo)
            ->where('is_substitute', true)
            ->where('substitute_date', $this->selectedDate)
            ->first();

        if ($existingSub && $existingSub->teacher_id != $substituteTeacherId) {
            // Delete the old one before creating new
            DB::table('timetables')->where('id', $existingSub->id)->delete();
        }

        // Logic Check: Is the selected substitute already busy?
        $isBusy = $this->checkIfTeacherIsBusy($substituteTeacherId, $periodNo, $classId);
        if ($isBusy) {
            $subTeacherName = collect($this->teachers)->firstWhere('id', $substituteTeacherId)->name ?? 'Teacher';
            $this->warningMessage = "Warning: {$subTeacherName} is already assigned to a class during Period {$periodNo}. Temporary assignment override applied.";
        }

        // Create Substitution
        DB::table('timetables')->insert([
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $substituteTeacherId,
            'day' => Carbon::parse($this->selectedDate)->format('l'),
            'period_no' => $periodNo,
            'room' => '', // Could copy from original
            'is_divided' => false,
            'is_substitute' => true,
            'substitute_date' => $this->selectedDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->substitutions[$absentTeacherId][$periodNo] = $substituteTeacherId;
        session()->flash('message', 'Substitute assigned successfully.');
    }

    public function checkIfTeacherIsBusy($teacherId, $periodNo, $classId = null)
    {
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');

        // Check regular classes (ignore if it's the exact same class - e.g. co-teacher in divided class)
        $hasRegular = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.period_no', $periodNo)
            ->where('timetables.is_substitute', false)
            ->when($classId, function($q) use ($classId) {
                return $q->where('timetables.class_id', '!=', $classId);
            })
            ->exists();

        if ($hasRegular) return true;

        // Check other substitutions
        $hasSubstitute = DB::table('timetables')
            ->where('teacher_id', $teacherId)
            ->where('period_no', $periodNo)
            ->where('substitute_date', $this->selectedDate)
            ->where('is_substitute', true)
            ->exists();

        return $hasSubstitute;
    }

    public function getAvailableTeachersForPeriod($periodNo, $currentlyAssignedId = null, $classId = null)
    {
        $busyTeacherIds = [];
        $dayOfWeek = Carbon::parse($this->selectedDate)->format('l');

        // 1. Teachers with regular classes (exclude if they are teaching the same class i.e., shared teacher)
        $regularBusy = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.period_no', $periodNo)
            ->where('timetables.is_substitute', false)
            ->when($classId, function($q) use ($classId) {
                return $q->where('timetables.class_id', '!=', $classId);
            })
            ->pluck('timetables.teacher_id')
            ->toArray();
        
        $busyTeacherIds = array_merge($busyTeacherIds, $regularBusy);

        // 2. Teachers already assigned as substitutes
        $subBusy = DB::table('timetables')
            ->where('substitute_date', $this->selectedDate)
            ->where('period_no', $periodNo)
            ->where('is_substitute', true)
            ->pluck('teacher_id')
            ->toArray();

        $busyTeacherIds = array_merge($busyTeacherIds, $subBusy);

        // 3. Teachers who are absent/leave
        $absentTeacherIds = [];
        foreach ($this->teacherStatuses as $tId => $status) {
            if ($status === 'Absent' || $status === 'Leave') {
                $absentTeacherIds[] = $tId;
            }
        }
        $busyTeacherIds = array_merge($busyTeacherIds, $absentTeacherIds);

        $busyTeacherIds = array_unique($busyTeacherIds);

        // Remove the currently assigned teacher from the "busy" list so they appear in the dropdown
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

        return DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayOfWeek)
            ->where('timetables.is_substitute', false)
            ->select('timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
            ->orderBy('timetables.period_no')
            ->get();
    }

    public function downloadPDF()
    {
        return redirect()->route('admin.substitutions.print', [
            'date' => $this->selectedDate,
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
                    'periods' => $teacherPeriods
                ];
            }
        }

        return $reportData;
    }

    public function render()
    {
        return view('livewire.admin.substitution-manager')->layout('components.layouts.admin', ['title' => 'Daily Substitutions']);
    }
}
