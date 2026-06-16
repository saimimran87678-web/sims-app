<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\Subject;

class ScheduleManager extends Component
{
    // Day Selection
    public $selectedDay = 'Monday';
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    public $applyToAllDays = false;

    // Data
    public $periods = [];
    public $classes = [];
    public $teachers = [];
    public $timetables = [];

    // Modal State
    public $showModal = false;
    public $editingId = null;
    public $modalClassId;
    public $modalPeriodNo;
    public $modalPeriodLabel;

    // Form Data
    public $selectedTeacherId = '';
    public $selectedSubjectId = '';
    public $room = '';
    public $isDivided = false;
    public $selectedTeacherId2 = '';
    public $selectedSubjectId2 = '';
    public $isSubstitute = false;
    public $substituteDate = '';
    public $substituteTeacherId = '';

    public $availableSubjects2 = [];
    public $availableSubstituteTeachers = [];

    // Session Management
    public $selectedSessionId;
    // public $academicSessions = []; // Actually needed for View. 
    // Wait, I should make public property. 
    // But Step 1251 shows I need to declare it. 
    public $academicSessions = [];

    public function mount()
    {
        $this->authorize('schedule.manage');

        // Set working days based on the global Weekend Mode setting
        $weekendMode = \App\Models\Setting::get('weekend_mode', 'sat_sun');
        $this->days = $weekendMode === 'sun_only'
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $this->academicSessions = \App\Models\AcademicSession::orderBy('start_date', 'desc')->get();
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        // Enforce Data Scope
        if (!auth()->user()->can('schedule.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
            $this->selectedSessionId = $activeSessionId;
            $this->academicSessions = $this->academicSessions->where('id', $activeSessionId);
        } else {
            $this->selectedSessionId = $activeSessionId;
        }

        $this->loadData();
        $this->substituteDate = now()->format('Y-m-d');
    }

    public function loadData()
    {
        $this->periods = PeriodConfig::orderBy('period_no')->get();
        
        if ($this->selectedSessionId) {
            $this->classes = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->selectedSessionId)
                ->orderBy('numeric_value')
                ->get();
        } else {
            $this->classes = collect();
        }

        $this->teachers = DB::table('users')->where('role', 'teacher')->orderBy('name')->get();
        $this->loadTimetables();
    }
    
    public function updatedSelectedSessionId()
    {
        $this->loadData();
    }

    public function loadTimetables()
    {
        // For "Everyday" mode, load Monday's schedule as the unified template
        $dayToLoad = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
        
        $this->timetables = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $dayToLoad)
            ->where('timetables.is_substitute', false)
            ->select('timetables.*')
            ->get()
            ->keyBy(fn($t) => $t->class_id . '_' . $t->period_no);
    }

    public function updatedSelectedDay()
    {
        $this->loadTimetables();
    }

    public function getSchedule($classId, $periodNo)
    {
        return $this->timetables[$classId . '_' . $periodNo] ?? null;
    }

    public function openModal($classId, $periodNo)
    {
        $period = $this->periods->firstWhere('period_no', $periodNo);
        if ($period && ($period->is_break || $period->is_assembly)) return;

        $this->resetModal();

        $this->modalClassId = $classId;
        $this->modalPeriodNo = $periodNo;
        $this->modalPeriodLabel = $period->label ?? "Period $periodNo";

        // Get class name for default room
        $class = $this->classes->firstWhere('id', $classId);
        $this->room = $class->name ?? '';

        // Load existing if editing
        $existing = $this->getSchedule($classId, $periodNo);
        if ($existing) {
            $this->editingId = $existing->id;
            $this->selectedTeacherId = $existing->teacher_id;
            $this->selectedSubjectId = $existing->subject_id;
            $this->room = $existing->room;
            $this->isDivided = $existing->is_divided;
        }

        // Load smart dropdowns
        $this->loadAvailableTeachers();
        $this->loadAvailableSubjects();

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetModal();
    }

    public function resetModal()
    {
        $this->editingId = null;
        $this->modalClassId = null;
        $this->modalPeriodNo = null;
        $this->modalPeriodLabel = '';
        $this->selectedTeacherId = '';
        $this->selectedSubjectId = '';
        $this->room = '';
        $this->isDivided = false;
        $this->selectedTeacherId2 = '';
        $this->selectedSubjectId2 = '';
        $this->isSubstitute = false;
        $this->substituteDate = now()->format('Y-m-d');
        $this->substituteTeacherId = '';
        $this->applyToAllDays = false;
    }



    public function loadAvailableTeachers()
    {
        // Get teachers already assigned in this period on this day within the selected session
        $busyTeacherIds = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $this->selectedDay)
            ->where('timetables.period_no', $this->modalPeriodNo)
            ->where('timetables.is_substitute', false)
            ->when($this->editingId, fn($q) => $q->where('timetables.id', '!=', $this->editingId))
            ->pluck('timetables.teacher_id')
            ->toArray();

        $this->availableTeachers = collect($this->teachers)
            ->filter(fn($t) => !in_array($t->id, $busyTeacherIds))
            ->values();

        // Substitute teachers - same logic
        $this->availableSubstituteTeachers = $this->availableTeachers;
    }

    public function loadAvailableSubjects()
    {
        // Get subjects for this class
        $classSubjects = Subject::where('class_id', $this->modalClassId)->get();

        // Get subjects already assigned to this class on this day
        $usedSubjectIds = DB::table('timetables')
            ->where('class_id', $this->modalClassId)
            ->where('day', $this->selectedDay)
            ->where('is_substitute', false)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->pluck('subject_id')
            ->toArray();

        $this->availableSubjects = $classSubjects->filter(fn($s) => !in_array($s->id, $usedSubjectIds))->values();
        $this->availableSubjects2 = $this->availableSubjects;
    }

    public function updatedSelectedSubjectId()
    {
        // Update available subjects for second dropdown (exclude first selection)
        if ($this->isDivided && $this->selectedSubjectId) {
            $this->availableSubjects2 = $this->availableSubjects->filter(fn($s) => $s->id != $this->selectedSubjectId)->values();
        }
    }

    public function save()
    {
        if (!$this->selectedTeacherId || !$this->selectedSubjectId) {
            session()->flash('error', 'Please select teacher and subject.');
            return;
        }

        // Determine which days to save to
        // In "Everyday" mode, automatically apply to all days
        if ($this->selectedDay === 'Everyday') {
            $daysToSave = $this->days;
        } elseif ($this->applyToAllDays) {
            $daysToSave = $this->days;
        } else {
            $daysToSave = [$this->selectedDay];
        }

        foreach ($daysToSave as $day) {
            // Check if entry already exists for this day (when applying to all)
            $existingEntry = null;
            if ($this->applyToAllDays && $day !== $this->selectedDay) {
                $existingEntry = DB::table('timetables')
                    ->where('class_id', $this->modalClassId)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('day', $day)
                    ->where('is_substitute', false)
                    ->first();
            }

            $data = [
                'class_id' => $this->modalClassId,
                'subject_id' => $this->selectedSubjectId,
                'teacher_id' => $this->selectedTeacherId,
                'day' => $day,
                'period_no' => $this->modalPeriodNo,
                'room' => $this->room,
                'is_divided' => $this->isDivided,
                'is_substitute' => false,
                'substitute_date' => null,
                'start_time' => null,
                'end_time' => null,
                'updated_at' => now(),
            ];

            if ($this->editingId && $day === $this->selectedDay) {
                DB::table('timetables')->where('id', $this->editingId)->update($data);
            } elseif ($existingEntry) {
                DB::table('timetables')->where('id', $existingEntry->id)->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('timetables')->insert($data);
            }

            // Handle divided class (second entry)
            if ($this->isDivided && $this->selectedTeacherId2 && $this->selectedSubjectId2) {
                $data2 = [
                    'class_id' => $this->modalClassId,
                    'subject_id' => $this->selectedSubjectId2,
                    'teacher_id' => $this->selectedTeacherId2,
                    'day' => $day,
                    'period_no' => $this->modalPeriodNo,
                    'room' => $this->room,
                    'is_divided' => true,
                    'is_substitute' => false,
                    'substitute_date' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                DB::table('timetables')->insert($data2);
            }
        }



        // Handle substitute
        if ($this->isSubstitute && $this->substituteTeacherId && $this->substituteDate) {
            $subData = [
                'class_id' => $this->modalClassId,
                'subject_id' => $this->selectedSubjectId,
                'teacher_id' => $this->substituteTeacherId,
                'day' => $this->selectedDay,
                'period_no' => $this->modalPeriodNo,
                'room' => $this->room,
                'is_divided' => false,
                'is_substitute' => true,
                'substitute_date' => $this->substituteDate,
                'start_time' => null,
                'end_time' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('timetables')->insert($subData);
        }

        session()->flash('message', 'Schedule saved successfully!');
        $this->closeModal();
        $this->loadTimetables();
    }

    public function delete()
    {
        if ($this->editingId) {
            DB::table('timetables')->where('id', $this->editingId)->delete();
            session()->flash('message', 'Schedule entry deleted.');
            $this->closeModal();
            $this->loadTimetables();
        }
    }

    public function copyToAllDays()
    {
        $classIds = $this->classes->pluck('id')->toArray();
        if (empty($classIds)) return;

        $currentDayEntries = DB::table('timetables')
            ->whereIn('class_id', $classIds)
            ->where('day', $this->selectedDay)
            ->where('is_substitute', false)
            ->get();

        if ($currentDayEntries->isEmpty()) {
            session()->flash('error', 'No schedule entries to copy for ' . $this->selectedDay);
            return;
        }

        $targetDays = collect($this->days)->filter(fn($d) => $d !== $this->selectedDay);

        foreach ($targetDays as $day) {
            // Delete existing entries for target day for current session's classes
            DB::table('timetables')
                ->whereIn('class_id', $classIds)
                ->where('day', $day)
                ->where('is_substitute', false)
                ->delete();

            // Copy current day entries
            foreach ($currentDayEntries as $entry) {
                DB::table('timetables')->insert([
                    'class_id' => $entry->class_id,
                    'subject_id' => $entry->subject_id,
                    'teacher_id' => $entry->teacher_id,
                    'day' => $day,
                    'period_no' => $entry->period_no,
                    'room' => $entry->room,
                    'is_divided' => $entry->is_divided,
                    'is_substitute' => false,
                    'substitute_date' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        session()->flash('message', $this->selectedDay . ' schedule copied to all weekdays!');
    }

    public function clearDay()
    {
        $classIds = $this->classes->pluck('id')->toArray();
        if (empty($classIds)) return;

        DB::table('timetables')
            ->whereIn('class_id', $classIds)
            ->where('day', $this->selectedDay)
            ->where('is_substitute', false)
            ->delete();

        session()->flash('message', 'All schedule entries for ' . $this->selectedDay . ' have been cleared.');
        $this->loadTimetables();
    }

    public function render()
    {
        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.schedule-manager')->layout($layout, ['title' => 'Schedule Management']);
    }
}
