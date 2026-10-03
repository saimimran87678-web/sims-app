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

    // View Mode: 'class' | 'teacher'
    public $viewMode = 'class';

    // Data
    public $periods = [];
    public $classes = [];
    public $teachers = [];
    public $timetables = [];

    // Teacher-view grid: [teacher_id => [period_no => [rows]]]
    public $teacherGridMap = [];

    // Modal State
    public $showModal = false;
    public $editingId = null;
    public $editingId2 = null;
    public $modalClassId;
    public $modalPeriodNo;
    public $modalPeriodLabel;

    // Form Data
    public $selectedTeacherId = '';
    public $selectedSubjectId = '';
    public $room = '';
    public $isDivided = false;
    public $dividedSlots = []; // Dynamic slots: [['id' => null, 'teacher_id' => '', 'subject_id' => '', 'room' => '']]
    public $selectedTeacherId2 = '';
    public $selectedSubjectId2 = '';
    public $isMerged = false;
    public $mergeGroupId = null;
    public $mergedClassIds = [];
    public $mergedPartnerClassesMap = [];
    public $availableSubjects = [];
    public $availableSubjects2 = [];
    public $availableTeachers = [];

    // Class Teacher Sync
    public $setAsClassTeacher = false;
    public $currentClassTeacherId = null;
    public $currentClassTeacherName = null;

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
        if ($this->selectedSessionId) {
            $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
            if ($shiftType === 'both') {
                $shiftType = 'morning';
            }

            $this->periods = PeriodConfig::where('shift_type', $shiftType)->orderBy('period_no')->get();

            $this->classes = Classes::withoutGlobalScope('active_session')
                ->leftJoin('session_user', function ($join) {
                    $join->on('classes.id', '=', 'session_user.class_id')
                         ->where('session_user.academic_session_id', '=', $this->selectedSessionId);
                })
                ->leftJoin('users', 'session_user.user_id', '=', 'users.id')
                ->where('classes.academic_session_id', $this->selectedSessionId)
                ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                    $q->where('classes.shift_type', $shiftType);
                })
                ->select('classes.*', 'users.name as class_teacher_name')
                ->orderBy('classes.numeric_value')
                ->get();
            $this->teachers = \App\Models\User::where('role', 'teacher')
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
        } else {
            $this->periods = collect();
            $this->classes = collect();
            $this->teachers = collect();
        }

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
        
        $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $rawRows = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $dayToLoad)
            ->where('timetables.is_substitute', false)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select('timetables.*', 'classes.name as class_name', 'classes.shift_type as class_shift')
            ->get();

        // Class-view grid: keyed by class_id_periodno
        $this->timetables = $rawRows->groupBy(fn($t) => $t->class_id . '_' . $t->period_no);

        // Teacher-view grid: [teacher_id => [period_no => [rows]]]
        $this->teacherGridMap = [];
        foreach ($rawRows as $row) {
            if (!$row->teacher_id) continue;
            $this->teacherGridMap[$row->teacher_id][$row->period_no][] = $row;
        }

        // Map merged partners for display
        $this->mergedPartnerClassesMap = [];
        $mergedGroups = $rawRows->where('is_merged', true)->groupBy('merge_group_id');
        foreach ($mergedGroups as $groupId => $groupRows) {
            if (!$groupId) continue;
            $classNames = $groupRows->pluck('class_name', 'class_id')->unique();
            foreach ($groupRows as $row) {
                $otherNames = $classNames->except($row->class_id)->values()->all();
                $this->mergedPartnerClassesMap[$row->id] = implode(', ', $otherNames);
            }
        }
    }

    public function updatedSelectedDay()
    {
        $this->loadTimetables();
    }

    public function updatedViewMode()
    {
        // Grid is rebuilt in render() from $timetables, no extra DB call needed
    }

    public function getSchedule($classId, $periodNo)
    {
        return $this->timetables[$classId . '_' . $periodNo] ?? collect();
    }

    public function openModal($classId = null, $periodNo = null, $teacherId = null)
    {
        $period = $this->periods->firstWhere('period_no', $periodNo);
        if ($period && ($period->is_break || $period->is_assembly)) return;

        $this->resetModal();

        $this->modalClassId = $classId ?: null;
        $this->modalPeriodNo = $periodNo;
        $this->modalPeriodLabel = $period->label ?? "Period $periodNo";

        if ($teacherId) {
            $this->selectedTeacherId = $teacherId;
        }

        if ($this->modalClassId) {
            // Get class name for default room
            $class = $this->classes->firstWhere('id', $this->modalClassId);
            $this->room = $class->name ?? '';

            // Load existing if editing
            $existingSchedules = $this->getSchedule($this->modalClassId, $periodNo);
            if ($existingSchedules->isNotEmpty()) {
                $existing = $existingSchedules->first();
                $this->editingId = $existing->id;
                $this->selectedTeacherId = $existing->teacher_id;
                $this->selectedSubjectId = $existing->subject_id;
                $this->room = $existing->room;
                $this->isDivided = (bool)$existing->is_divided;
                $this->isMerged = (bool)($existing->is_merged ?? false);
                $this->mergeGroupId = $existing->merge_group_id ?? null;

                if ($this->isDivided && $existingSchedules->count() > 1) {
                    $this->dividedSlots = [];
                    $extraSchedules = $existingSchedules->slice(1)->values();
                    foreach ($extraSchedules as $slotIdx => $sched) {
                        $this->dividedSlots[] = [
                            'id' => $sched->id,
                            'teacher_id' => $sched->teacher_id,
                            'subject_id' => $sched->subject_id,
                            'room' => $sched->room ?? '',
                        ];
                        if ($slotIdx === 0) {
                            $this->editingId2 = $sched->id;
                            $this->selectedTeacherId2 = $sched->teacher_id;
                            $this->selectedSubjectId2 = $sched->subject_id;
                        }
                    }
                } elseif ($this->isDivided) {
                    $this->dividedSlots = [
                        ['id' => null, 'teacher_id' => '', 'subject_id' => '', 'room' => '']
                    ];
                }

                if ($this->isMerged && $this->mergeGroupId) {
                    $this->mergedClassIds = DB::table('timetables')
                        ->where('merge_group_id', $this->mergeGroupId)
                        ->where('class_id', '!=', $this->modalClassId)
                        ->where('is_substitute', false)
                        ->pluck('class_id')
                        ->unique()
                        ->toArray();
                }
            }

            // Load current class teacher for this class in this academic session
            $this->loadClassTeacherInfo($this->modalClassId);

            // Load available subjects
            $this->loadAvailableSubjects();
        } else {
            $this->availableSubjects = collect();
            $this->availableSubjects2 = collect();
        }

        // Load smart dropdowns
        $this->loadAvailableTeachers();

        $this->showModal = true;
    }

    public function updatedModalClassId($value)
    {
        $this->modalClassId = $value ?: null;
        $this->selectedSubjectId = '';
        $this->selectedSubjectId2 = '';

        if ($this->modalClassId) {
            $class = $this->classes->firstWhere('id', $this->modalClassId);
            if (empty($this->room) || $this->classes->pluck('name')->contains($this->room)) {
                $this->room = $class->name ?? '';
            }

            $this->loadClassTeacherInfo($this->modalClassId);
            $this->loadAvailableSubjects();
        } else {
            $this->availableSubjects = collect();
            $this->availableSubjects2 = collect();
            $this->currentClassTeacherId = null;
            $this->currentClassTeacherName = null;
            $this->setAsClassTeacher = false;
        }

        $this->loadAvailableTeachers();
    }

    public function loadClassTeacherInfo($classId)
    {
        if (!$classId) {
            $this->currentClassTeacherId = null;
            $this->currentClassTeacherName = null;
            $this->setAsClassTeacher = false;
            return;
        }

        $currentClassTeacher = DB::table('session_user')
            ->join('users', 'session_user.user_id', '=', 'users.id')
            ->where('session_user.academic_session_id', $this->selectedSessionId)
            ->where('session_user.class_id', $classId)
            ->select('users.id', 'users.name')
            ->first();

        $this->currentClassTeacherId = $currentClassTeacher?->id;
        $this->currentClassTeacherName = $currentClassTeacher?->name;

        // Default checkbox to true if selected teacher is already the active class teacher
        if ($this->selectedTeacherId && $this->currentClassTeacherId && $this->selectedTeacherId == $this->currentClassTeacherId) {
            $this->setAsClassTeacher = true;
        } else {
            $this->setAsClassTeacher = false;
        }
    }

    public function updatedSelectedTeacherId($value)
    {
        if ($value && $this->currentClassTeacherId && $value == $this->currentClassTeacherId) {
            $this->setAsClassTeacher = true;
        } else {
            $this->setAsClassTeacher = false;
        }
    }

    public function getBusyClassIdsProperty()
    {
        if (!$this->modalPeriodNo) return [];
        $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;

        return DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $dayToCheck)
            ->where('timetables.period_no', $this->modalPeriodNo)
            ->where('timetables.is_substitute', false)
            ->when($this->editingId, fn($q) => $q->where('timetables.id', '!=', $this->editingId))
            ->when($this->editingId2 ?? false, fn($q) => $q->where('timetables.id', '!=', $this->editingId2))
            ->when($this->mergeGroupId, fn($q) => $q->where(function($subQ) {
                $subQ->whereNull('timetables.merge_group_id')
                     ->orWhere('timetables.merge_group_id', '!=', $this->mergeGroupId);
            }))
            ->pluck('timetables.class_id')
            ->toArray();
    }

    public function getAvailableMergeClassesProperty()
    {
        $classId = (int) $this->modalClassId;

        $query = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId);

        if ($classId) {
            $query->where('id', '!=', $classId);

            $currentClass = Classes::withoutGlobalScope('active_session')->find($classId);
            if ($currentClass && !empty($currentClass->shift_type) && !in_array($currentClass->shift_type, ['regular', 'both'])) {
                $query->where(function($q) use ($currentClass) {
                    $q->where('shift_type', $currentClass->shift_type)
                      ->orWhereNull('shift_type');
                });
            }
        }

        return $query->orderBy('numeric_value')->orderBy('name')->get();
    }

    public function updatedIsDivided($value)
    {
        if ($value && empty($this->dividedSlots)) {
            $this->dividedSlots = [
                [
                    'id' => $this->editingId2 ?: null,
                    'teacher_id' => $this->selectedTeacherId2 ?: '',
                    'subject_id' => $this->selectedSubjectId2 ?: '',
                    'room' => '',
                ]
            ];
        }
    }

    public function addDividedSlot()
    {
        if (count($this->dividedSlots) < 5) {
            $this->dividedSlots[] = [
                'id' => null,
                'teacher_id' => '',
                'subject_id' => '',
                'room' => '',
            ];
        }
    }

    public function removeDividedSlot($index)
    {
        if (isset($this->dividedSlots[$index])) {
            unset($this->dividedSlots[$index]);
            $this->dividedSlots = array_values($this->dividedSlots);
            $this->selectedTeacherId2 = $this->dividedSlots[0]['teacher_id'] ?? '';
            $this->selectedSubjectId2 = $this->dividedSlots[0]['subject_id'] ?? '';
            $this->editingId2 = $this->dividedSlots[0]['id'] ?? null;
        }
    }

    public function updatedSelectedTeacherId2($value)
    {
        if (empty($this->dividedSlots)) {
            $this->dividedSlots = [['id' => $this->editingId2, 'teacher_id' => $value, 'subject_id' => $this->selectedSubjectId2, 'room' => '']];
        } else {
            $this->dividedSlots[0]['teacher_id'] = $value;
        }
    }

    public function updatedSelectedSubjectId2($value)
    {
        if (empty($this->dividedSlots)) {
            $this->dividedSlots = [['id' => $this->editingId2, 'teacher_id' => $this->selectedTeacherId2, 'subject_id' => $value, 'room' => '']];
        } else {
            $this->dividedSlots[0]['subject_id'] = $value;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetModal();
    }

    public function resetModal()
    {
        $this->editingId = null;
        $this->editingId2 = null;
        $this->modalClassId = null;
        $this->modalPeriodNo = null;
        $this->modalPeriodLabel = '';
        $this->selectedTeacherId = '';
        $this->selectedSubjectId = '';
        $this->room = '';
        $this->isDivided = false;
        $this->dividedSlots = [];
        $this->selectedTeacherId2 = '';
        $this->selectedSubjectId2 = '';
        $this->isMerged = false;
        $this->mergeGroupId = null;
        $this->mergedClassIds = [];
        $this->applyToAllDays = false;
        $this->setAsClassTeacher = false;
        $this->currentClassTeacherId = null;
        $this->currentClassTeacherName = null;
    }

    public function loadAvailableTeachers()
    {
        $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;

        // Get teachers already assigned in this period on this day within the selected session
        $busyQuery = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $dayToCheck)
            ->where('timetables.period_no', $this->modalPeriodNo)
            ->where('timetables.is_substitute', false);

        if ($this->editingId) {
            $busyQuery->where('timetables.id', '!=', $this->editingId);
        }

        if (!empty($this->dividedSlots)) {
            $dividedIds = array_filter(array_column($this->dividedSlots, 'id'));
            if (!empty($dividedIds)) {
                $busyQuery->whereNotIn('timetables.id', $dividedIds);
            }
        }

        if ($this->editingId2) {
            $busyQuery->where('timetables.id', '!=', $this->editingId2);
        }

        // If editing an existing merged period, allow the teacher in partner classes of the same merge group
        if ($this->mergeGroupId) {
            $busyQuery->where(function($q) {
                $q->whereNull('timetables.merge_group_id')
                  ->orWhere('timetables.merge_group_id', '!=', $this->mergeGroupId);
            });
        }

        // If currently selecting partner classes to merge, exclude those partner classes from busy check
        if ($this->isMerged && !empty($this->mergedClassIds)) {
            $busyQuery->whereNotIn('timetables.class_id', $this->mergedClassIds);
        }

        $busyTeacherIds = $busyQuery->pluck('timetables.teacher_id')->toArray();

        $this->availableTeachers = collect($this->teachers)
            ->filter(fn($t) => !in_array($t->id, $busyTeacherIds) || ($this->selectedTeacherId && $t->id == $this->selectedTeacherId))
            ->values();
    }

    public function loadAvailableSubjects()
    {
        if (!$this->modalClassId) {
            $this->availableSubjects = collect();
            $this->availableSubjects2 = collect();
            return;
        }

        // Get subjects for this class
        $classSubjects = Subject::where('class_id', $this->modalClassId)->get();

        // Get subjects already assigned to this class on this day
        $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
        $usedSubjectIds = DB::table('timetables')
            ->where('class_id', $this->modalClassId)
            ->where('day', $dayToCheck)
            ->where('is_substitute', false)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->when($this->editingId2 ?? false, fn($q) => $q->where('id', '!=', $this->editingId2))
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
        if (!$this->modalClassId) {
            session()->flash('error', 'Please select a class.');
            return;
        }

        if (!$this->selectedTeacherId || !$this->selectedSubjectId) {
            session()->flash('error', 'Please select teacher and subject.');
            return;
        }

        // Validate divided slots: all slots that have a teacher must also have a subject
        foreach ($this->dividedSlots as $idx => $slot) {
            if (!empty($slot['teacher_id']) && empty($slot['subject_id'])) {
                session()->flash('error', 'Each divided teacher slot must have a subject assigned.');
                return;
            }
        }

        $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;

        // Collect IDs of divided slots being edited (to exclude from conflict check)
        $editingDividedIds = array_filter(array_column($this->dividedSlots, 'id'));

        // Check if class already has a period assigned at this time (unless editing same or divided)
        $existingClassEntries = DB::table('timetables')
            ->where('class_id', $this->modalClassId)
            ->where('day', $dayToCheck)
            ->where('period_no', $this->modalPeriodNo)
            ->where('is_substitute', false)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->when(!empty($editingDividedIds), fn($q) => $q->whereNotIn('id', $editingDividedIds))
            ->get();

        if ($existingClassEntries->isNotEmpty() && !$this->isDivided) {
            $existingTeacher = collect($this->teachers)->firstWhere('id', $existingClassEntries->first()->teacher_id)?->name ?? 'Another teacher';
            session()->flash('error', "Class already has an assigned period with {$existingTeacher} in Period {$this->modalPeriodNo}. Enable 'Divided Class' to co-teach.");
            return;
        }

        // Resolve merge group ID
        $mergeGroupId = null;
        if ($this->isMerged && !empty($this->mergedClassIds)) {
            $mergeGroupId = $this->mergeGroupId ?: (string) \Illuminate\Support\Str::uuid();
        }

        // Determine which days to save to
        if ($this->selectedDay === 'Everyday') {
            $daysToSave = $this->days;
        } elseif ($this->applyToAllDays) {
            $daysToSave = $this->days;
        } else {
            $daysToSave = [$this->selectedDay];
        }

        // Build full list of slots: primary slot + divided slots
        $allSlots = [
            [
                'id'         => $this->editingId,
                'teacher_id' => $this->selectedTeacherId,
                'subject_id' => $this->selectedSubjectId,
                'room'       => $this->room,
                'is_primary' => true,
            ]
        ];
        if ($this->isDivided) {
            foreach ($this->dividedSlots as $slot) {
                if (!empty($slot['teacher_id']) && !empty($slot['subject_id'])) {
                    $allSlots[] = [
                        'id'         => $slot['id'] ?? null,
                        'teacher_id' => $slot['teacher_id'],
                        'subject_id' => $slot['subject_id'],
                        'room'       => $slot['room'] ?? $this->room,
                        'is_primary' => false,
                    ];
                }
            }
        }

        // IDs of slots we will save/update (only for the selected day)
        $savedIds = [];

        foreach ($daysToSave as $day) {
            foreach ($allSlots as $slotDef) {
                $data = [
                    'class_id'       => $this->modalClassId,
                    'subject_id'     => $slotDef['subject_id'],
                    'teacher_id'     => $slotDef['teacher_id'],
                    'day'            => $day,
                    'period_no'      => $this->modalPeriodNo,
                    'room'           => $slotDef['room'],
                    'is_divided'     => $this->isDivided,
                    'is_merged'      => $this->isMerged,
                    'merge_group_id' => $mergeGroupId,
                    'is_substitute'  => false,
                    'substitute_date'=> null,
                    'start_time'     => null,
                    'end_time'       => null,
                    'updated_at'     => now(),
                ];

                if ($slotDef['id'] && $day === $this->selectedDay) {
                    DB::table('timetables')->where('id', $slotDef['id'])->update($data);
                    $savedIds[] = $slotDef['id'];
                } else {
                    // For "apply to all days" we look for an existing entry on that day
                    $existingForDay = null;
                    if ($this->applyToAllDays && $day !== $this->selectedDay) {
                        // Try to find matching slot on that day for this teacher+subject
                        $existingForDay = DB::table('timetables')
                            ->where('class_id', $this->modalClassId)
                            ->where('period_no', $this->modalPeriodNo)
                            ->where('day', $day)
                            ->where('teacher_id', $slotDef['teacher_id'])
                            ->where('is_substitute', false)
                            ->first();
                    }
                    if ($existingForDay) {
                        DB::table('timetables')->where('id', $existingForDay->id)->update($data);
                    } else {
                        $data['created_at'] = now();
                        DB::table('timetables')->insert($data);
                    }
                }
            }

            // Remove old divided sibling rows for this class/period/day that are no longer in our slot list
            if ($day === $this->selectedDay) {
                $keepIds = array_filter(array_column($allSlots, 'id'));
                DB::table('timetables')
                    ->where('class_id', $this->modalClassId)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('day', $day)
                    ->where('is_substitute', false)
                    ->when(!empty($keepIds), fn($q) => $q->whereNotIn('id', $keepIds))
                    ->when(empty($keepIds), fn($q) => $q->where('id', '!=', $this->editingId ?? 0))
                    ->delete();
            }

            // ── PERIOD MERGE: sync partner classes ──────────────────────────────
            if ($this->isMerged && $mergeGroupId && !empty($this->mergedClassIds)) {
                foreach ($this->mergedClassIds as $partnerClassId) {
                    $partnerData = [
                        'class_id'       => $partnerClassId,
                        'subject_id'     => $this->selectedSubjectId,
                        'teacher_id'     => $this->selectedTeacherId,
                        'day'            => $day,
                        'period_no'      => $this->modalPeriodNo,
                        'room'           => $this->room,
                        'is_divided'     => false,
                        'is_merged'      => true,
                        'merge_group_id' => $mergeGroupId,
                        'is_substitute'  => false,
                        'substitute_date'=> null,
                        'start_time'     => null,
                        'end_time'       => null,
                        'updated_at'     => now(),
                    ];

                    $existingPartner = DB::table('timetables')
                        ->where('class_id', $partnerClassId)
                        ->where('period_no', $this->modalPeriodNo)
                        ->where('day', $day)
                        ->where('is_substitute', false)
                        ->first();

                    if ($existingPartner) {
                        DB::table('timetables')->where('id', $existingPartner->id)->update($partnerData);
                    } else {
                        $partnerData['created_at'] = now();
                        DB::table('timetables')->insert($partnerData);
                    }

                    // Sync subject_allocations for partner class
                    DB::table('subject_allocations')->updateOrInsert(
                        ['class_id' => $partnerClassId, 'subject_id' => $this->selectedSubjectId],
                        ['user_id' => $this->selectedTeacherId, 'updated_at' => now()]
                    );
                }
            } elseif (!$this->isMerged && $this->mergeGroupId) {
                // Admin un-merged: remove partner entries that share the old merge group
                DB::table('timetables')
                    ->where('merge_group_id', $this->mergeGroupId)
                    ->where('class_id', '!=', $this->modalClassId)
                    ->where('day', $day)
                    ->delete();

                // Clear merge columns on primary entry
                DB::table('timetables')
                    ->where('class_id', $this->modalClassId)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('day', $day)
                    ->where('is_substitute', false)
                    ->update(['is_merged' => false, 'merge_group_id' => null, 'updated_at' => now()]);
            }
        }

        // Sync Class Teacher assignment with session_user and User Management
        if ($this->setAsClassTeacher && $this->selectedTeacherId && $this->modalClassId) {
            // 1. Clear previous class teacher for this class in this session if different
            DB::table('session_user')
                ->where('academic_session_id', $this->selectedSessionId)
                ->where('class_id', $this->modalClassId)
                ->where('user_id', '!=', $this->selectedTeacherId)
                ->update([
                    'class_id' => null,
                    'class_subject' => null,
                    'updated_at' => now(),
                ]);

            // 2. Fetch subject name for class_subject
            $subjectObj = Subject::find($this->selectedSubjectId);
            $subjectName = $subjectObj ? $subjectObj->name : null;

            // 3. Assign this teacher as class teacher in session_user
            DB::table('session_user')->updateOrInsert(
                [
                    'user_id' => $this->selectedTeacherId,
                    'academic_session_id' => $this->selectedSessionId,
                ],
                [
                    'class_id' => $this->modalClassId,
                    'class_subject' => $subjectName,
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );

            // 4. Keep users table aligned
            DB::table('users')->where('id', $this->selectedTeacherId)->update([
                'class_id' => $this->modalClassId,
                'class_subject' => $subjectName,
                'updated_at' => now(),
            ]);

            if ($this->currentClassTeacherId && $this->currentClassTeacherId != $this->selectedTeacherId) {
                DB::table('users')->where('id', $this->currentClassTeacherId)->where('class_id', $this->modalClassId)->update([
                    'class_id' => null,
                    'class_subject' => null,
                    'updated_at' => now(),
                ]);
            }
        } elseif (!$this->setAsClassTeacher && $this->currentClassTeacherId && $this->selectedTeacherId == $this->currentClassTeacherId) {
            // Admin explicitly unchecked the class teacher box for this teacher
            DB::table('session_user')
                ->where('academic_session_id', $this->selectedSessionId)
                ->where('user_id', $this->selectedTeacherId)
                ->where('class_id', $this->modalClassId)
                ->update([
                    'class_id' => null,
                    'class_subject' => null,
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('id', $this->selectedTeacherId)
                ->where('class_id', $this->modalClassId)
                ->update([
                    'class_id' => null,
                    'class_subject' => null,
                    'updated_at' => now(),
                ]);
        }

        // Sync Subject Allocation for Gradebook, Results, and Teacher Portal
        // Primary slot
        if ($this->selectedTeacherId && $this->selectedSubjectId && $this->modalClassId) {
            DB::table('subject_allocations')->updateOrInsert(
                ['class_id' => $this->modalClassId, 'subject_id' => $this->selectedSubjectId],
                ['user_id' => $this->selectedTeacherId, 'updated_at' => now()]
            );
        }

        // All divided slots
        if ($this->isDivided) {
            foreach ($this->dividedSlots as $slot) {
                if (!empty($slot['teacher_id']) && !empty($slot['subject_id'])) {
                    DB::table('subject_allocations')->updateOrInsert(
                        ['class_id' => $this->modalClassId, 'subject_id' => $slot['subject_id']],
                        ['user_id' => $slot['teacher_id'], 'updated_at' => now()]
                    );
                }
            }
        }

        session()->flash('message', 'Schedule saved successfully!');
        $this->closeModal();
        $this->loadData();
    }

    public function delete()
    {
        if (!$this->editingId) return;

        $entry = DB::table('timetables')->where('id', $this->editingId)->first();
        if (!$entry) return;

        // Delete primary entry
        DB::table('timetables')->where('id', $this->editingId)->delete();

        // Delete all sibling divided entries for same class/period/day
        if ($entry->is_divided) {
            DB::table('timetables')
                ->where('class_id', $entry->class_id)
                ->where('period_no', $entry->period_no)
                ->where('day', $entry->day)
                ->where('is_divided', true)
                ->where('is_substitute', false)
                ->delete();
        }

        // Delete all partner merged entries for the same merge group
        if ($entry->merge_group_id) {
            DB::table('timetables')
                ->where('merge_group_id', $entry->merge_group_id)
                ->where('is_substitute', false)
                ->delete();
        }

        // Re-evaluate subject allocation for this class & subject
        if ($entry->class_id && $entry->subject_id) {
            $remainingTeacher = DB::table('timetables')
                ->where('class_id', $entry->class_id)
                ->where('subject_id', $entry->subject_id)
                ->where('is_substitute', false)
                ->value('teacher_id');

            if ($remainingTeacher) {
                DB::table('subject_allocations')->updateOrInsert(
                    ['class_id' => $entry->class_id, 'subject_id' => $entry->subject_id],
                    ['user_id' => $remainingTeacher, 'updated_at' => now()]
                );
            } else {
                DB::table('subject_allocations')
                    ->where('class_id', $entry->class_id)
                    ->where('subject_id', $entry->subject_id)
                    ->delete();
            }
        }

        session()->flash('message', 'Schedule entry deleted.');
        $this->closeModal();
        $this->loadData();
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

                if ($entry->teacher_id && $entry->subject_id && $entry->class_id) {
                    DB::table('subject_allocations')->updateOrInsert(
                        [
                            'class_id' => $entry->class_id,
                            'subject_id' => $entry->subject_id,
                        ],
                        [
                            'user_id' => $entry->teacher_id,
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        session()->flash('message', $this->selectedDay . ' schedule copied to all weekdays!');
        $this->loadData();
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

        // Clean up any subject allocations that no longer exist anywhere in the week
        $remainingTimetables = DB::table('timetables')
            ->whereIn('class_id', $classIds)
            ->where('is_substitute', false)
            ->select('class_id', 'subject_id', 'teacher_id')
            ->distinct()
            ->get();

        $activeKeys = $remainingTimetables->map(fn($t) => $t->class_id . '_' . $t->subject_id)->toArray();

        DB::table('subject_allocations')
            ->whereIn('class_id', $classIds)
            ->get()
            ->each(function($alloc) use ($activeKeys) {
                if (!in_array($alloc->class_id . '_' . $alloc->subject_id, $activeKeys)) {
                    DB::table('subject_allocations')->where('id', $alloc->id)->delete();
                }
            });

        session()->flash('message', 'All schedule entries for ' . $this->selectedDay . ' have been cleared.');
        $this->loadData();
    }

    public function syncAllocations()
    {
        if (!$this->selectedSessionId) {
            session()->flash('error', 'No active session selected.');
            return;
        }

        $sessionClassIds = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId)
            ->pluck('id');

        $timetables = DB::table('timetables')
            ->whereIn('class_id', $sessionClassIds)
            ->where('is_substitute', false)
            ->whereNotNull('teacher_id')
            ->whereNotNull('subject_id')
            ->select('class_id', 'subject_id', 'teacher_id')
            ->distinct()
            ->get();

        $count = 0;
        foreach ($timetables as $t) {
            DB::table('subject_allocations')->updateOrInsert(
                [
                    'class_id' => $t->class_id,
                    'subject_id' => $t->subject_id,
                ],
                [
                    'user_id' => $t->teacher_id,
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        session()->flash('message', "Successfully synchronized {$count} subject allocation(s) from Timetable into Gradebook and User Management!");
        $this->loadData();
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
