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
    public $editingId2 = null;
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

        $this->timetables = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.day', $dayToLoad)
            ->where('timetables.is_substitute', false)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select('timetables.*')
            ->get()
            ->groupBy(fn($t) => $t->class_id . '_' . $t->period_no);
    }

    public function updatedSelectedDay()
    {
        $this->loadTimetables();
    }

    public function getSchedule($classId, $periodNo)
    {
        return $this->timetables[$classId . '_' . $periodNo] ?? collect();
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
        $existingSchedules = $this->getSchedule($classId, $periodNo);
        if ($existingSchedules->isNotEmpty()) {
            $existing = $existingSchedules->first();
            $this->editingId = $existing->id;
            $this->selectedTeacherId = $existing->teacher_id;
            $this->selectedSubjectId = $existing->subject_id;
            $this->room = $existing->room;
            $this->isDivided = $existing->is_divided;

            if ($this->isDivided && $existingSchedules->count() > 1) {
                $second = $existingSchedules->last();
                $this->editingId2 = $second->id;
                $this->selectedTeacherId2 = $second->teacher_id;
                $this->selectedSubjectId2 = $second->subject_id;
            }
        }

        // Load current class teacher for this class in this academic session
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

        // Load smart dropdowns
        $this->loadAvailableTeachers();
        $this->loadAvailableSubjects();

        $this->showModal = true;
    }

    public function updatedSelectedTeacherId($value)
    {
        if ($value && $this->currentClassTeacherId && $value == $this->currentClassTeacherId) {
            $this->setAsClassTeacher = true;
        } else {
            $this->setAsClassTeacher = false;
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
        $this->selectedTeacherId2 = '';
        $this->selectedSubjectId2 = '';
        $this->applyToAllDays = false;
        $this->setAsClassTeacher = false;
        $this->currentClassTeacherId = null;
        $this->currentClassTeacherName = null;
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
            ->when($this->editingId2 ?? false, fn($q) => $q->where('timetables.id', '!=', $this->editingId2))
            ->pluck('timetables.teacher_id')
            ->toArray();

        $this->availableTeachers = collect($this->teachers)
            ->filter(fn($t) => !in_array($t->id, $busyTeacherIds))
            ->values();
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
                    'updated_at' => now(),
                ];
                
                if ($this->editingId2 && $day === $this->selectedDay) {
                    DB::table('timetables')->where('id', $this->editingId2)->update($data2);
                } else {
                    $data2['created_at'] = now();
                    DB::table('timetables')->insert($data2);
                }
            } else {
                // If it was divided but is no longer divided, delete the second entry
                if ($this->editingId2 && $day === $this->selectedDay) {
                    DB::table('timetables')->where('id', $this->editingId2)->delete();
                }
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
        if ($this->selectedTeacherId && $this->selectedSubjectId && $this->modalClassId) {
            DB::table('subject_allocations')->updateOrInsert(
                [
                    'class_id' => $this->modalClassId,
                    'subject_id' => $this->selectedSubjectId,
                ],
                [
                    'user_id' => $this->selectedTeacherId,
                    'updated_at' => now(),
                ]
            );
        }

        if ($this->isDivided && $this->selectedTeacherId2 && $this->selectedSubjectId2 && $this->modalClassId) {
            DB::table('subject_allocations')->updateOrInsert(
                [
                    'class_id' => $this->modalClassId,
                    'subject_id' => $this->selectedSubjectId2,
                ],
                [
                    'user_id' => $this->selectedTeacherId2,
                    'updated_at' => now(),
                ]
            );
        }

        session()->flash('message', 'Schedule saved successfully!');
        $this->closeModal();
        $this->loadData();
    }

    public function delete()
    {
        if ($this->editingId) {
            $entry = DB::table('timetables')->where('id', $this->editingId)->first();
            DB::table('timetables')->where('id', $this->editingId)->delete();

            // Re-evaluate subject allocation for this class & subject
            if ($entry && $entry->class_id && $entry->subject_id) {
                $remainingTeacher = DB::table('timetables')
                    ->where('class_id', $entry->class_id)
                    ->where('subject_id', $entry->subject_id)
                    ->where('is_substitute', false)
                    ->value('teacher_id');

                if ($remainingTeacher) {
                    DB::table('subject_allocations')->updateOrInsert(
                        [
                            'class_id' => $entry->class_id,
                            'subject_id' => $entry->subject_id,
                        ],
                        [
                            'user_id' => $remainingTeacher,
                            'updated_at' => now(),
                        ]
                    );
                } else {
                    // No teacher scheduled for this subject in this class anymore
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
