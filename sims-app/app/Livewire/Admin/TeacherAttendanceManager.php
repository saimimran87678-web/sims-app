<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\TeacherAttendance;
use App\Models\Substitution;
use App\Models\Timetable;
use App\Models\PeriodConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherAttendanceManager extends Component
{
    public $date;
    public $teachers = [];
    public $attendanceData = []; // [user_id => status]
    public $remarksData = []; // [user_id => remarks]
    
    // Substitution Modal
    public $showSubstitutionModal = false;
    public $selectedTeacherId = null;
    public $selectedTeacherName = '';
    public $absentTeacherSchedule = [];
    public $availableTeachers = [];
    public $periodLabels = []; // [template_id_period_no => label]
    public $substitutionData = []; // [timetable_id => substitute_teacher_id]

    protected $rules = [
        'attendanceData.*' => 'required|in:present,absent,late,leave,official_duty',
        'remarksData.*' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->loadTeachersAndAttendance();
    }

    public function updatedDate()
    {
        $this->loadTeachersAndAttendance();
    }

    public function loadTeachersAndAttendance()
    {
        // Get all teachers
        $this->teachers = User::where('role', 'teacher')->orderBy('name')->get();
        
        // Get existing attendance for this date
        $attendances = TeacherAttendance::where('date', $this->date)->get();

        $this->attendanceData = [];
        $this->remarksData = [];

        foreach ($this->teachers as $teacher) {
            $record = $attendances->firstWhere('user_id', $teacher->id);
            if ($record) {
                $this->attendanceData[$teacher->id] = $record->status;
                $this->remarksData[$teacher->id] = $record->remarks;
            } else {
                // Default to present if no record exists yet, or null/unmarked
                // Let's set default to 'present' for easy marking, or maybe null if we want explicit action
                // Implementation Plan said: "Status (Select/Radio)"
                $this->attendanceData[$teacher->id] = 'present';
                $this->remarksData[$teacher->id] = '';
            }
        }
    }

    public function saveAttendance()
    {
        $this->validate();
        
        DB::beginTransaction();
        try {
            foreach ($this->attendanceData as $userId => $status) {
                $attendance = TeacherAttendance::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'date' => $this->date
                    ],
                    [
                        'status' => $status,
                        'remarks' => $this->remarksData[$userId] ?? null
                    ]
                );

                // If status is changed to 'present', remove any existing substitutions
                if ($status === 'present') {
                    Substitution::where('teacher_attendance_id', $attendance->id)->delete();
                }
            }
            DB::commit();
            session()->flash('message', 'Attendance saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error saving attendance: ' . $e->getMessage());
        }
    }

    public function markAllPresent()
    {
        foreach ($this->teachers as $teacher) {
            $this->attendanceData[$teacher->id] = 'present';
        }
    }

    public function openSubstitutionModal($teacherId)
    {
        $this->selectedTeacherId = $teacherId;
        $teacher = $this->teachers->firstWhere('id', $teacherId);
        $this->selectedTeacherName = $teacher ? $teacher->name : 'Unknown';
        
        $this->loadAbsentTeacherSchedule($teacherId);
        $this->loadAbsentTeacherSchedule($teacherId);
            
        $this->showSubstitutionModal = true;
    }

    public function closeSubstitutionModal()
    {
        $this->showSubstitutionModal = false;
        $this->selectedTeacherId = null;
        $this->absentTeacherSchedule = [];
        $this->substitutionData = [];
    }

    public function loadAbsentTeacherSchedule($teacherId)
    {
        $dayOfWeek = Carbon::parse($this->date)->format('l');

        // Fetch regular timetable for this teacher on this day
        // Note: This logic assumes 'day' column stores "Monday", "Tuesday", etc.
        $this->absentTeacherSchedule = Timetable::with(['class', 'subject', 'subject2'])
            ->where('teacher_id', $teacherId)
            ->where('day', $dayOfWeek)
            ->orderBy('period_no')
            ->get();

        // Load existing substitutions
        // First find the teacher attendance record id
        $attendance = TeacherAttendance::where('user_id', $teacherId)
            ->where('date', $this->date)
            ->first();

        $this->substitutionData = [];

        if ($attendance) {
            $existingSubs = Substitution::where('teacher_attendance_id', $attendance->id)->get();
            foreach ($existingSubs as $sub) {
                $this->substitutionData[$sub->timetable_id] = $sub->substitute_teacher_id;
            }
        }

        // Calculate functionality available teachers per period
        $this->availableTeachers = [];
        $allTeachers = User::where('role', 'teacher')
            ->where('id', '!=', $teacherId)
            ->orderBy('name')
            ->get();

        // Get all timetables for OTHER teachers on this day to check conflicts
        $allTimetables = Timetable::where('day', $dayOfWeek)
            ->where('teacher_id', '!=', $teacherId)
            ->get()
            ->groupBy('teacher_id');

        // NEW: Fetch Substitution Counts for the Day
        $subCounts = Substitution::where('date', $this->date)
            ->select('substitute_teacher_id', DB::raw('count(*) as total'))
            ->groupBy('substitute_teacher_id')
            ->pluck('total', 'substitute_teacher_id');

        // NEW: Fetch Booked Substitutes per Period to prevent double booking
        // simple map: period_no => [teacher_id, teacher_id]
        $bookedSubstitutes = Substitution::where('date', $this->date)
            ->with('timetable') // Load timetable to get period_no
            ->get()
            ->groupBy(function($sub) {
                return $sub->timetable->period_no ?? null; // Group by period
            });

        // Update names with count
        $allTeachers->transform(function ($teacher) use ($subCounts) {
            $count = $subCounts[$teacher->id] ?? 0;
            // Always show count e.g. "Mr. Fawad (0)" or "Mr. Fawad (1)"
            $teacher->name = $teacher->name . " ($count)";
            return $teacher;
        });

        foreach ($this->absentTeacherSchedule as $sched) {
            // For each period the absent teacher has a class, find who is free
            $period = $sched->period_no;
            
            $freeTeachers = $allTeachers->filter(function($teacher) use ($allTimetables, $period, $bookedSubstitutes) {
                $teacherSchedule = $allTimetables->get($teacher->id);
                
                // NEW: Check dynamic attendance status
                // If the teacher is not marked as 'present' in the current session, they cannot be a substitute.
                // We use $this->attendanceData which holds the current radio button selection.
                $status = $this->attendanceData[$teacher->id] ?? null;
                if ($status !== 'present') {
                    return false;
                }
                
                if (!$teacherSchedule) return true; // No classes at all today

                // Check if they have a class at this period
                // We must check strict period overlap or just period_no match
                // Assuming period_no is the standard integer index
                $isBusy = $teacherSchedule->contains('period_no', $period);
                
                if ($isBusy) return false;

                // Check if they are already booked as a SUBSTITUTE for this period
                $bookedInPeriod = $bookedSubstitutes[$period] ?? collect();
                if ($bookedInPeriod->contains('substitute_teacher_id', $teacher->id)) {
                    return false; 
                }

                return true;
            }); // Keep as collection for mapping

            // Store as plain array to preserve name changes (Livewire model hydration fix)
            $this->availableTeachers[$sched->id] = $freeTeachers->map(function($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name
                ];
            })->values()->all();
        }

        // NEW: Fetch Period Labels
        $templateIds = $this->absentTeacherSchedule->pluck('schedule_template_id')->unique();
        $configs = PeriodConfig::whereIn('schedule_template_id', $templateIds)->get();
        
        $this->periodLabels = [];
        foreach ($configs as $config) {
            $key = $config->schedule_template_id . '_' . $config->period_no;
            $this->periodLabels[$key] = $config->label ?? ('Period ' . $config->period_no);
        }
    }

    public function saveSubstitutions()
    {
        Log::info("saveSubstitutions called. SelectedTeacherID: " . ($this->selectedTeacherId ?? 'NULL'));

        if (!$this->selectedTeacherId) {
            Log::warning("saveSubstitutions aborting: No selected teacher ID.");
            return;
        }

        // Ensure teacher is marked as absent
        
        DB::beginTransaction();
        try {
            Log::info("Saving substitutions for TeacherID {$this->selectedTeacherId} on {$this->date}", ['data' => $this->substitutionData]);

            // 1. Ensure Attendance Record Exists
            $attendance = TeacherAttendance::firstOrCreate(
                [
                    'user_id' => $this->selectedTeacherId,
                    'date' => $this->date
                ],
                [
                    'status' => $this->attendanceData[$this->selectedTeacherId] ?? 'absent', // Use selected status (absent, late, official_duty)
                    'remarks' => $this->remarksData[$this->selectedTeacherId] ?? 'Auto-generated for substitution'
                ]
            );

            // 2. Save Substitutions
            foreach ($this->substitutionData as $timetableId => $substituteId) {
                if ($substituteId) {
                    Log::info("  - Updating ID $timetableId with Sub $substituteId");
                    Substitution::updateOrCreate(
                        [
                            'teacher_attendance_id' => $attendance->id,
                            'timetable_id' => $timetableId,
                            'date' => $this->date
                        ],
                        [
                            'substitute_teacher_id' => $substituteId,
                            'status' => 'assigned'
                        ]
                    );
                } else {
                    // specific removal of substitution if user selected "None"/Empty
                    Substitution::where('teacher_attendance_id', $attendance->id)
                        ->where('timetable_id', $timetableId)
                        ->delete();
                }
            }
            
            DB::commit();
            session()->flash('message', 'Substitutions updated successfully.');
            $this->closeSubstitutionModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ERROR saving substitutions: " . $e->getMessage());
            session()->flash('error', 'Error saving substitutions: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.teacher-attendance-manager')
            ->layout('components.layouts.admin', ['title' => 'Teacher Attendance']);
    }
}
