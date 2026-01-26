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
    public $includeSaturday = false;
    public $applyToAllDays = false;
    public $viewMode = 'class'; // 'class' or 'teacher'

    // Data
    public $periods = [];
    public $classes = [];
    public $teachers = [];
    public $timetables = [];

    // Template Properties
    public $scheduleTemplates = [];
    public $selectedTemplateId;
    public $activeTemplateId;
    public $newTemplateName = '';

    // Modal State
    public $showModal = false;
    public $editingId = null;
    public $modalClassId;
    public $modalTeacherId; // For Teacher View
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
    
    // Search Properties for Modal Inputs
    public $searchSubjectInput = '';
    public $searchTeacherInput = '';
    public $searchTeacher2Input = '';
    public $searchSubInput = ''; // Specific for Class View Substitute
    
    // Selection State
    public $availableSubjects2 = [];
    public $availableSubstituteTeachers = [];
    public $availableTeachers = [];
    public $availableSubjects = [];
    public $availableClasses = []; // For Teacher View dropdown

    // Double Subject Support
    public $isDoubleSubject = false;

    // Room Mode
    public $rooms = [];
    public $searchClass = '';
    public $selectedRoomClass = null; // For By Room class selector
    
    // Teacher Mode
    public $searchTeacher = '';
    public $selectedTeacherViewId = null; // For By Teacher selector
    
    // Substitution Mode (New Logic)
    public $substituteTargetTeacherId = null; // The teacher who is ABSENT
    public $searchSubstituteTeacher = ''; 
    public $substituteTargetSchedule = null; // The class/subject to cover
    
    // Conflict Handling
    public $showConflictWarning = false;
    public $conflictDetails = null;
    public $confirmedConflict = false; // To bypass check
    
    // Substitute Options
    public $showAllSubstituteTeachers = false;
    
    // Pre-loaded subjects for template rendering (avoid N+1)
    public $subjects = [];
    
    // Pre-loaded second teacher IDs for divided classes (avoid N+1)
    public $dividedTeachers = [];

    // Session Management
    public $selectedSessionId;
    public $academicSessions = [];

    public function confirmConflictAssignment()
    {
        $this->confirmedConflict = true;
        $this->showConflictWarning = false;
        $this->save();
    }

    public function cancelConflictAssignment()
    {
        $this->showConflictWarning = false;
        $this->conflictDetails = null;
        $this->confirmedConflict = false;
    }

    public function mount()
    {
        $this->authorize('schedule.manage');
        
        $this->academicSessions = \App\Models\AcademicSession::orderBy('start_date', 'desc')->get();
        $activeSessionId = $this->academicSessions->where('is_active', true)->first()->id ?? $this->academicSessions->first()->id ?? null;

        // Load Templates
        $this->scheduleTemplates = \App\Models\ScheduleTemplate::all();
        $activeTemplate = $this->scheduleTemplates->where('is_active', true)->first();
        
        $this->activeTemplateId = $activeTemplate?->id;
        $this->selectedTemplateId = $this->activeTemplateId ?? $this->scheduleTemplates->first()?->id;

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
        // Sync Saturday Setting
        $template = \App\Models\ScheduleTemplate::find($this->selectedTemplateId);
        if ($template) {
            $this->includeSaturday = $template->is_saturday_working;
            if ($this->includeSaturday) {
                $this->days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            } else {
                $this->days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                if ($this->selectedDay === 'Saturday') $this->selectedDay = 'Monday';
            }
        }

        // Load Periods for selected Template and Day
        $day = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
        
        $this->periods = PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)
            ->get()
            ->filter(function($p) use ($day) {
                // Return generic configs (null days) or specific day configs
                 $days = $p->days; 
                 if (empty($days)) return true; // Generic
                 return in_array($day, $days); // Specific
            })
            ->sortBy('period_no')
            ->values();
        
        if ($this->selectedSessionId) {
            $this->classes = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->selectedSessionId)
                ->orderBy('numeric_value')
                ->get();
        } else {
            $this->classes = collect();
        }

        $this->teachers = DB::table('users')->where('role', 'teacher')->orderBy('name')->get();
        
        // Pre-load all subjects to avoid N+1 queries in template
        $this->subjects = Subject::all()->keyBy('id');
        
        $this->loadTimetables();
    }
    
    public function updatedSelectedSessionId()
    {
        $this->loadData();
    }

    public function updatedSelectedTemplateId()
    {
        $this->loadData();
    }

    public function createTemplate()
    {
        $this->validate(['newTemplateName' => 'required|min:3']);
        
        $template = \App\Models\ScheduleTemplate::create([
            'name' => $this->newTemplateName,
            'description' => 'Created ' . now()->format('Y-m-d'),
            'is_active' => false,
        ]);

        // Copy Period Configs from Default/Active template to new one?
        // User said "I need current period configuration time and period arrangment".
        // So yes, clone periods from currently selected template.
        $sourcePeriods = PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)->get();
        foreach ($sourcePeriods as $p) {
            PeriodConfig::create([
                'schedule_template_id' => $template->id,
                'period_no' => $p->period_no,
                'start_time' => $p->start_time,
                'end_time' => $p->end_time,
                'is_break' => $p->is_break,
                'is_assembly' => $p->is_assembly,
                'label' => $p->label,
                'days' => $p->days,
            ]);
        }

        $this->newTemplateName = '';
        $this->scheduleTemplates = \App\Models\ScheduleTemplate::all();
        $this->selectedTemplateId = $template->id;
        $this->loadData();
        session()->flash('message', 'New schedule template created!');
    }

    public function activateTemplate($id)
    {
        DB::transaction(function() use ($id) {
            \App\Models\ScheduleTemplate::query()->update(['is_active' => false]);
            \App\Models\ScheduleTemplate::where('id', $id)->update(['is_active' => true]);
        });
        
        $this->activeTemplateId = $id;
        $this->scheduleTemplates = \App\Models\ScheduleTemplate::all();
        session()->flash('message', 'Schedule activated successfully!');
    }

    public function deleteTemplate($id)
    {
        $template = \App\Models\ScheduleTemplate::find($id);

        if (!$template) return;

        if ($template->is_active) {
            session()->flash('error', 'Cannot delete the active schedule. Please activate another schedule first.');
            return;
        }

        if ($this->scheduleTemplates->count() <= 1) {
            session()->flash('error', 'Cannot delete the only remaining schedule.');
            return;
        }

        $template->delete();

        // If we deleted the currently selected template, switch to active or first available
        if ($this->selectedTemplateId == $id) {
             $this->selectedTemplateId = $this->activeTemplateId ?? $this->scheduleTemplates->where('id', '!=', $id)->first()?->id;
        }

        $this->scheduleTemplates = \App\Models\ScheduleTemplate::all();
        $this->loadData();
        session()->flash('message', 'Schedule template deleted.');
    }

    public function updatedViewMode()
    {
        $this->loadTimetables();
    }

    public function loadTimetables()
    {
        // For "Everyday" mode, load Monday's schedule as the unified template
        $dayToLoad = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
        
        $query = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $this->selectedSessionId)
            ->where('timetables.schedule_template_id', $this->selectedTemplateId)
            ->select('timetables.*');

        // $this->specificDate handling logic follows below
        
        // Let's use specificDate property
        if ($this->specificDate) {
             $dayOfWeek = \Carbon\Carbon::parse($this->specificDate)->format('l');
             $query->where(function($q) use ($dayOfWeek) {
                 $q->where(function($sub) use ($dayOfWeek) {
                     $sub->where('timetables.day', $dayOfWeek)->where('timetables.is_substitute', false);
                 })->orWhere(function($sub) {
                     $sub->where('timetables.substitute_date', $this->specificDate)->where('timetables.is_substitute', true);
                 });
             })->orderBy('timetables.is_substitute'); // Ensure substitute overwrites regular
        } else {
             $query->where('timetables.day', $dayToLoad)
                   ->where('timetables.is_substitute', false);
        }

        $rawTimetables = $query->get();
        
        // Pre-build second teacher lookup for divided classes (avoid N+1 in template)
        $this->dividedTeachers = [];
        $dividedEntries = $rawTimetables->filter(fn($t) => $t->is_divided);
        
        // Group by class_id + period_no to find pairs
        $grouped = $rawTimetables->groupBy(fn($t) => $t->class_id . '_' . $t->period_no);
        foreach ($grouped as $key => $entries) {
            if ($entries->count() > 1) {
                // Store the second teacher's ID for the first entry
                $first = $entries->first();
                $second = $entries->skip(1)->first();
                if ($first && $second) {
                    $this->dividedTeachers[$first->id] = $second->teacher_id;
                    $this->dividedTeachers[$second->id] = $first->teacher_id;
                }
            }
        }
        
        $this->timetables = $rawTimetables;

        if ($this->viewMode === 'teacher') {
            $this->timetables = $this->timetables->keyBy(fn($t) => $t->teacher_id . '_' . $t->period_no);
        } elseif ($this->viewMode === 'room') {
            // Room mode now shows a selected class's schedule (like Class mode)
            // So we key by class_id + period_no for compatibility with getSchedule()
            $this->rooms = DB::table('timetables')
                ->where('schedule_template_id', $this->selectedTemplateId)
                ->where('is_substitute', false)
                ->whereNotNull('room')
                ->where('room', '!=', '')
                ->distinct()
                ->pluck('room')
                ->sort()
                ->values();
                
            // Key by class_id like Class mode since we display selected class schedule
            $this->timetables = $this->timetables->keyBy(fn($t) => $t->class_id . '_' . $t->period_no);
        } else {
            $this->timetables = $this->timetables->keyBy(fn($t) => $t->class_id . '_' . $t->period_no);
        }
    }
    
    public $specificDate = null;

    public function updatedSpecificDate()
    {
        if ($this->specificDate) {
            // Update selectedDay to match the date
            $this->selectedDay = \Carbon\Carbon::parse($this->specificDate)->format('l');
        }
        $this->loadTimetables();
    }

    public function updatedSelectedDay()
    {
        $this->loadTimetables();
    }

    public function getSchedule($rowId, $periodNo)
    {
        // rowId is classId (if viewMode='class') or teacherId (if viewMode='teacher')
        return $this->timetables[$rowId . '_' . $periodNo] ?? null;
    }

    public function openModal($rowId, $periodNo)
    {
        $period = $this->periods->firstWhere('period_no', $periodNo);
        if ($period && ($period->is_break || $period->is_assembly)) return;

        $this->resetModal();
        $this->modalPeriodNo = $periodNo;
        $this->modalPeriodLabel = $period->label ?? "Period $periodNo";

        if ($this->viewMode === 'teacher') {
            $this->modalTeacherId = $rowId;
            $this->selectedTeacherId = $rowId; // Pre-select current teacher
            $teacher = $this->teachers->firstWhere('id', $rowId);
            $this->room = ''; // No default room for teacher view initially, rely on class or user input
            $this->loadAvailableClasses();
            $this->loadAvailableTeachers(); // Load available teachers for the dropdown
            $this->loadAvailableSubjects(); // Load available subjects for the dropdown
        } elseif ($this->viewMode === 'room') {
            // In Room mode, rowId is the Class ID (from selectedRoomClass)
            $this->modalClassId = $rowId;
            $class = $this->classes->firstWhere('id', $rowId);
            $this->room = $class->name ?? '';
            $this->loadAvailableTeachers();
            $this->loadAvailableSubjects();
        } else {
            $this->modalClassId = $rowId;
            $class = $this->classes->firstWhere('id', $rowId);
            $this->room = $class->name ?? '';
            $this->loadAvailableTeachers();
            $this->loadAvailableSubjects();
        }

        // Load existing rule:
        // 1. If specificDate is set (Substitution Mode), prioritize finding a Substitute Entry.
        // 2. If no substitute found (or not in Sub mode), load Regular Entry.

        $existing = null;
        $dayToUse = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;

        // Step 1: Check for Substitute
        if ($this->specificDate) {
             $subQuery = DB::table('timetables')
                ->where('substitute_date', $this->specificDate)
                ->where('period_no', $periodNo)
                ->where('is_substitute', true);
             
             if ($this->viewMode === 'teacher') {
                 // In teacher view, we clicked a slot. 
                 // If the teacher has a substitution (either they are the sub, or they are being subbed out?)
                 // Usually Teacher View shows what the teacher is doing.
                 // If they are the substitute, it shows here.
                 $subQuery->where('teacher_id', $rowId);
             } else {
                 $subQuery->where('class_id', $rowId);
             }
             $existing = $subQuery->first();
        }

        // Step 2: If no substitute loaded, load Regular Entry
        if (!$existing) {
             $regQuery = DB::table('timetables')
                ->where('day', $dayToUse)
                ->where('period_no', $periodNo)
                ->where('is_substitute', false)
                ->where('schedule_template_id', $this->selectedTemplateId);

            if ($this->viewMode === 'teacher') {
                $regQuery->where('teacher_id', $rowId);
            } else {
                $regQuery->where('class_id', $rowId);
            }
            $existing = $regQuery->first();
        }

        // Fallback: Check memory cache if DB returned nothing (state sync)
        if (!$existing) { 
             $existing = $this->getSchedule($rowId, $periodNo); 
        }

        if ($existing) {
            $this->editingId = $existing->id;
            
            if ($existing->is_substitute) {
                // Load Substitute Data
                $this->isSubstitute = true;
                $this->substituteDate = $existing->substitute_date;
                $this->substituteTeacherId = $existing->teacher_id;
                
                // Also load the underlying class/subject context so the form looks right
                $this->modalClassId = $existing->class_id;
                // Popoulate Search
                $this->searchClass = $this->classes->firstWhere('id', $this->modalClassId)->name ?? '';

                $this->selectedSubjectId = $existing->subject_id;
                $this->room = $existing->room;
                
                // For regular fields (teacher view), we might want to show the Original Teacher?
                // But the form dropdowns are for "Teacher".
                // In Sub Mode, the "Teacher" dropdown is the Substitute Teacher.
            } else {
                // Load Regular Data
                if ($this->viewMode === 'teacher') {
                    $this->modalClassId = $existing->class_id; 
                    $this->searchClass = $this->classes->firstWhere('id', $this->modalClassId)->name ?? '';
                    $this->loadAvailableSubjects();
                    // Teacher is already set by row selection, but also set from DB
                    $this->selectedTeacherId = $existing->teacher_id;
                } elseif ($this->viewMode === 'room') {
                    $this->modalClassId = $existing->class_id; 
                    $this->searchClass = $this->classes->firstWhere('id', $this->modalClassId)->name ?? '';
                    // In Room mode, we need to set the teacher from DB
                    $this->selectedTeacherId = $existing->teacher_id;
                    $this->loadAvailableSubjects();
                } else {
                    // Class mode
                    $this->selectedTeacherId = $existing->teacher_id;
                }
                
                $this->selectedSubjectId = $existing->subject_id;
                $this->selectedSubjectId2 = $existing->subject_id_2 ?? null;
                $this->isDoubleSubject = !empty($existing->subject_id_2);
                $this->room = $existing->room;
                $this->isDivided = (bool)$existing->is_divided;
                
                // Load second teacher for divided class
                if ($this->isDivided) {
                    // Find the second entry for this divided class
                    $dayToUse = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
                    $secondEntry = DB::table('timetables')
                        ->where('class_id', $this->modalClassId)
                        ->where('day', $dayToUse)
                        ->where('period_no', $this->modalPeriodNo)
                        ->where('is_substitute', false)
                        ->where('schedule_template_id', $this->selectedTemplateId)
                        ->where('id', '!=', $existing->id)
                        ->first();
                    
                    if ($secondEntry) {
                        $this->selectedTeacherId2 = $secondEntry->teacher_id;
                        $this->selectedSubjectId2 = $secondEntry->subject_id;
                    }
                }

                // Refresh available lists after loading existing data (for proper conflict filtering)
                $this->loadAvailableClasses();
                $this->loadAvailableTeachers();
                $this->loadAvailableSubjects();
                
                // Refresh subject lists after setting ID
                $this->updatedSelectedSubjectId();
            }
        }

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
        $this->modalTeacherId = null;
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
        
        $this->reset(['modalClassId', 'modalTeacherId', 'modalPeriodNo', 'modalPeriodLabel', 
            'selectedTeacherId', 'selectedTeacherId2', 'selectedSubjectId', 'selectedSubjectId2', 'room',
            'isDoubleSubject', 'isDivided', 'editingId', 'searchClass', 'searchTeacherInput', 'searchSubjectInput',
            'isSubstitute', 'substituteDate', 'substituteTeacherId', 'substituteTargetSchedule', 'substituteTargetTeacherId', 'searchSubstituteTeacher', 'searchSubInput'
        ]);
        
        $this->showConflictWarning = false;
        $this->conflictDetails = null;
        $this->confirmedConflict = false;

        $this->applyToAllDays = false;
        $this->isDoubleSubject = false;
        $this->showAllTeachers = false;
    }


    
    public function selectSubstituteTargetTeacher($id)
    {
        $this->substituteTargetTeacherId = $id;
        $this->substituteTargetSchedule = null;
        $this->searchSubstituteTeacher = $this->teachers->firstWhere('id', $id)->name ?? '';
        
        if (!$this->substituteTargetTeacherId || !$this->modalPeriodNo) {
            return;
        }

        // Determine Day based on Substitute Date if set, otherwise Selected Day
        $day = $this->selectedDay;
        if ($this->substituteDate) {
             $day = \Carbon\Carbon::parse($this->substituteDate)->format('l');
        }
        if ($day === 'Everyday') $day = 'Monday';

        // Find what the Target Teacher (Absent) is doing at this time
        $targetEntry = DB::table('timetables')
            ->where('teacher_id', $this->substituteTargetTeacherId)
            ->where('day', $day)
            ->where('period_no', $this->modalPeriodNo)
            ->where('is_substitute', false) // We want their regular class
            ->where('schedule_template_id', $this->selectedTemplateId)
            ->first();

        if ($targetEntry) {
             $this->substituteTargetSchedule = $targetEntry;
             
             // Auto-populate form
             $this->modalClassId = $targetEntry->class_id;
             $this->searchClass = $this->classes->firstWhere('id', $targetEntry->class_id)->name ?? '';
             
             // Set room to class name (default logic) or existing room
             $this->room = $targetEntry->room ?: $this->searchClass;
             
             $this->selectedSubjectId = $targetEntry->subject_id;
             $this->loadAvailableSubjects(); // Refresh lists
        }
    }

    public function updatedSubstituteDate()
    {
        if ($this->substituteTargetTeacherId) {
            $this->selectSubstituteTargetTeacher($this->substituteTargetTeacherId);
        }
    }

    public function updatedIncludeSaturday()
    {
        if ($this->includeSaturday) {
            $this->days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        } else {
            $this->days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            if ($this->selectedDay === 'Saturday') {
                $this->selectedDay = 'Monday';
                $this->loadTimetables();
            }
        }
    }

    public function loadAvailableClasses() {
        // Filter classes that are already assigned in this period
        if (!$this->modalPeriodNo) {
            $this->availableClasses = $this->classes;
            return;
        }

        $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;

        $busyClassIds = DB::table('timetables')
            ->where('day', $dayToCheck)
            ->where('period_no', $this->modalPeriodNo)
            ->where('is_substitute', false)
            ->where('schedule_template_id', $this->selectedTemplateId)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->pluck('class_id')
            ->toArray();

        $this->availableClasses = $this->classes->filter(fn($c) => !in_array($c->id, $busyClassIds))->values();
    }

    public function updatedModalClassId() {
        if ($this->viewMode === 'teacher') {
             $this->loadAvailableSubjects(); // Reload subjects when class changes
             // Auto-fill room with Class Name as per user request
             $class = $this->classes->firstWhere('id', $this->modalClassId);
             if ($class) {
                 $this->room = $class->name;
             }
        }
    }

    public function selectClass($id)
    {
        $this->modalClassId = $id;
        $class = $this->classes->firstWhere('id', $id);
        $this->searchClass = $class->name ?? '';
        
        // Auto-set room to Class Name
        if ($class) {
            $this->room = $class->name;
        }
        
        $this->loadAvailableSubjects();
    }

    public function selectRoomClass($id)
    {
        $this->selectedRoomClass = $id;
        $this->searchClass = $this->classes->firstWhere('id', $id)->name ?? '';
    }

    public function clearRoomClass()
    {
        $this->selectedRoomClass = null;
        $this->searchClass = '';
    }

    public function selectTeacherView($id)
    {
        $this->selectedTeacherViewId = $id;
        $this->searchTeacher = collect($this->teachers)->firstWhere('id', $id)->name ?? '';
    }

    public function clearTeacherView()
    {
        $this->selectedTeacherViewId = null;
        $this->searchTeacher = '';
    }
    
    public $showAllTeachers = false; // Regular Schedule Toggle

    public function updatedShowAllTeachers()
    {
        $this->loadAvailableTeachers();
    }
    
    public function updatedSelectedTeacherId()
    {
        // Check for conflict if Show All is enabled and teacher selected
        if ($this->selectedTeacherId && $this->showAllTeachers) {
            $busyIds = $this->getBusyTeacherIds();
            if (in_array($this->selectedTeacherId, $busyIds)) {
                $teacher = $this->teachers->firstWhere('id', $this->selectedTeacherId);
                
                // Find what they are doing
                $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
                $conflict = DB::table('timetables')
                    ->where('teacher_id', $this->selectedTeacherId)
                    ->where('day', $dayToCheck)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('is_substitute', false)
                    ->where('schedule_template_id', $this->selectedTemplateId)
                    ->first();
                    
                if ($conflict) {
                    $cClass = $this->classes->firstWhere('id', $conflict->class_id)->name ?? 'Unknown Class';
                    $this->conflictDetails = "Teacher {$teacher->name} is already assigned to {$cClass} related to {$dayToCheck}";
                    $this->showConflictWarning = true;
                }
            } else {
                 $this->showConflictWarning = false;
            }
        } else {
             $this->showConflictWarning = false;
        }
    }

    public function getBusyTeacherIds()
    {
        $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
        
        $excludeIds = [];
        if ($this->editingId) {
            $excludeIds[] = $this->editingId;
            if ($this->isDivided && $this->modalClassId && $this->modalPeriodNo) {
                 // Logic for second entry exclusion (same as before)
                 $secondEntryId = DB::table('timetables')
                    ->where('class_id', $this->modalClassId)
                    ->where('day', $dayToCheck)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('is_substitute', false)
                    ->where('schedule_template_id', $this->selectedTemplateId)
                    ->where('id', '!=', $this->editingId)
                    ->value('id');
                 if ($secondEntryId) $excludeIds[] = $secondEntryId;
            }
        }
        
        return DB::table('timetables')
            ->where('day', $dayToCheck)
            ->where('period_no', $this->modalPeriodNo)
            ->where('is_substitute', false)
            ->where('schedule_template_id', $this->selectedTemplateId)
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->pluck('teacher_id')
            ->toArray();
    }

    public function loadAvailableTeachers()
    {
        $busyTeacherIds = $this->getBusyTeacherIds();

        if ($this->showAllTeachers) {
            $this->availableTeachers = collect($this->teachers)->values();
        } else {
            $this->availableTeachers = collect($this->teachers)
                ->filter(fn($t) => !in_array($t->id, $busyTeacherIds))
                ->values();
        }

        // Substitute teachers logic remains same
        if ($this->showAllSubstituteTeachers) {
             $this->availableSubstituteTeachers = collect($this->teachers)->values();
        } else {
             // For substitutes, we might want to filter by busyTeacherIds (Regular duties) as well?
             // Existing logic was $this->availableTeachers (which was filtered).
             // If showAllTeachers is on, availableTeachers is ALL.
             // But availableSubstituteTeachers should probably default to filtered unless its own toggle is on.
             
             // Re-calculate filtered list for substitutes if needed, or just use filtered list.
             $this->availableSubstituteTeachers = collect($this->teachers)
                ->filter(fn($t) => !in_array($t->id, $busyTeacherIds))
                ->values();
        }
    }

    public function updatedShowAllSubstituteTeachers()
    {
        $this->loadAvailableTeachers();
    }

    public function loadAvailableSubjects()
    {
        if (!$this->modalClassId) {
            $this->availableSubjects = [];
            return;
        }

        // Get subjects for this class
        $classSubjects = Subject::where('class_id', $this->modalClassId)->get();

        // For "Everyday" mode, check against Monday (reference day)
        $dayToCheck = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;

        // When editing a divided class, we need to exclude BOTH entries from the used check
        $excludeIds = [];
        if ($this->editingId) {
            $excludeIds[] = $this->editingId;
            
            // If editing a divided class, also exclude the second entry
            if ($this->isDivided && $this->modalClassId && $this->modalPeriodNo) {
                $secondEntryId = DB::table('timetables')
                    ->where('class_id', $this->modalClassId)
                    ->where('day', $dayToCheck)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('is_substitute', false)
                    ->where('schedule_template_id', $this->selectedTemplateId)
                    ->where('id', '!=', $this->editingId)
                    ->value('id');
                    
                if ($secondEntryId) {
                    $excludeIds[] = $secondEntryId;
                }
            }
        }

        // Get subjects already assigned to this class on this day (ANY period)
        // This ensures: If Math is assigned to Period 1, it won't appear in Period 2, 3, etc.
        $usedSubjectIds = DB::table('timetables')
            ->where('class_id', $this->modalClassId)
            ->where('day', $dayToCheck)
            ->where('is_substitute', false)
            ->where('schedule_template_id', $this->selectedTemplateId)
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->pluck('subject_id')
            ->toArray();
        
        // Also get subject_id_2 (double subjects)
        $usedSubjectIds2 = DB::table('timetables')
            ->where('class_id', $this->modalClassId)
            ->where('day', $dayToCheck)
            ->where('is_substitute', false)
            ->where('schedule_template_id', $this->selectedTemplateId)
            ->whereNotNull('subject_id_2')
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->pluck('subject_id_2')
            ->toArray();
        
        $allUsedSubjectIds = array_unique(array_merge($usedSubjectIds, $usedSubjectIds2));

        // Allow re-use of subjects (e.g. for Divided Classes or Double Periods)
        $this->availableSubjects = $classSubjects->values();
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
        // Validation changes based on context
        // In Teacher View: modalClassId needs to be selected
        // In Class View: selectedTeacherId needs to be selected
        
        $rules = [
            'modalClassId' => 'required',
            'modalPeriodNo' => 'required',
        ];

        if ($this->isSubstitute) {
            $rules['substituteDate'] = 'required|date';
            // In Class View, we need substituteTeacherId. In Teacher View, selectedTeacherId (context).
            if ($this->viewMode !== 'teacher') {
                $rules['substituteTeacherId'] = 'required';
            } else {
                $rules['selectedTeacherId'] = 'required';
            }
        } else {
            $rules['selectedTeacherId'] = 'required';
            $rules['selectedSubjectId'] = 'required';
        }

        $this->validate($rules, [
            'modalClassId.required' => 'Please select a class.',
            'selectedTeacherId.required' => 'Please select a teacher.',
            'selectedSubjectId.required' => 'Please select a subject.',
            'substituteTeacherId.required' => 'Please select a substitute teacher.',
        ]);

        // Conflict Block for Regular Schedule
        if (!$this->isSubstitute && $this->showConflictWarning && !$this->confirmedConflict) {
             return; // Block save until confirmed
        }

        // Validate Weekend Rules for Substitutions
        if ($this->isSubstitute && $this->substituteDate) {
             $date = \Carbon\Carbon::parse($this->substituteDate);
             $day = $date->format('l');
             
             if ($day === 'Saturday' && !$this->includeSaturday) {
                 $this->addError('substituteDate', 'Saturday is marked as off day in settings.');
                 // Force refresh or just stop
                 return; 
             }
             if ($day === 'Sunday') {
                 $this->addError('substituteDate', 'Cannot assign substitutions on Sunday.');
                 return;
             }
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

        // Only save Regular Schedule if we are NOT in Teacher View doing a Substitution
        $skipRegularSave = ($this->viewMode === 'teacher' && $this->isSubstitute);

        if (!$skipRegularSave) {
            foreach ($daysToSave as $day) {
                // Check if entry already exists for this day (Upsert Logic)
                $existingEntry = null;
                
                // If we have an editingId and it matches the day, we trust it.
                // But if we don't have editingId (or day differs), we MUST check DB to match Class+Period+Day to avoid duplicates.
                
                $query = DB::table('timetables')
                    ->where('class_id', $this->modalClassId)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('day', $day)
                    ->where('is_substitute', false)
                    ->where('schedule_template_id', $this->selectedTemplateId);
                    
                $existingEntry = $query->first();

                $data = [
                    'class_id' => $this->modalClassId,
                    'subject_id' => $this->selectedSubjectId,
                    'subject_id_2' => ($this->isDoubleSubject && !empty($this->selectedSubjectId2)) ? $this->selectedSubjectId2 : null,
                    'teacher_id' => $this->selectedTeacherId,
                    'schedule_template_id' => $this->selectedTemplateId,
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
                    // Updating the specific record we opened
                    DB::table('timetables')->where('id', $this->editingId)->update($data);
                } elseif ($existingEntry) {
                    // Found a conflict/existing record -> Update it instead of inserting
                    DB::table('timetables')->where('id', $existingEntry->id)->update($data);
                } else {
                    // Truly new record
                    $data['created_at'] = now();
                    DB::table('timetables')->insert($data);
                }

                // Handle divided class (second entry)
                if ($this->isDivided && $this->selectedTeacherId2 && $this->selectedSubjectId2) {
                    $data2 = [
                        'class_id' => $this->modalClassId,
                        'subject_id' => $this->selectedSubjectId2,
                        'teacher_id' => $this->selectedTeacherId2,
                        'schedule_template_id' => $this->selectedTemplateId,
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
        }

        // Handle substitute
        // Determine correct substitute teacher ID based on view mode
        $actualSubTeacherId = $this->substituteTeacherId;
        if ($this->viewMode === 'teacher') {
             $actualSubTeacherId = $this->selectedTeacherId; // Context is the Sub Teacher
        }

        if ($this->isSubstitute && $actualSubTeacherId && $this->substituteDate && !$this->confirmedConflict) {
             // 1. Check Date Day
             $dateCarbon = \Carbon\Carbon::parse($this->substituteDate);
             $dayName = $dateCarbon->format('l'); // e.g. Monday
             
             // 2. Check Regular Schedule Conflict (unless negated by a sub entry of "Free"? No, simplistic check first)
             // We check if they have a regular class on this Day AND no substitution record overriding it (or maybe check raw)
             // Simpler: Check if they have ANY regular class for this period/day
             
             $conflict = DB::table('timetables')
                ->where('teacher_id', $actualSubTeacherId)
                ->where('day', $dayName)
                ->where('period_no', $this->modalPeriodNo)
                ->where('is_substitute', false)
                ->where('schedule_template_id', $this->selectedTemplateId)
                ->first();
                
             // 3. Check Substitute Conflict (Already assigned as sub elsewhere today)
             // Note: A teacher can be assigned multiple substitutions IF they are covering different periods.
             // But for THIS period, they can only do one.
             if (!$conflict) {
                 $conflict = DB::table('timetables')
                    ->where('teacher_id', $actualSubTeacherId)
                    ->where('substitute_date', $this->substituteDate)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('is_substitute', true)
                    ->where('schedule_template_id', $this->selectedTemplateId)
                    ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                    ->first();
             }
             
             if ($conflict) {
                 // Fetch Details
                 $cClass = $this->classes->firstWhere('id', $conflict->class_id)->name ?? '';
                 // Safe subject lookup
                 $cSubject = '';
                 if (isset($this->subjects) && is_iterable($this->subjects)) {
                     $cSubject = collect($this->subjects)->firstWhere('id', $conflict->subject_id)->name ?? '';
                 }
                 if (!$cSubject) {
                      $cSubject = \App\Models\Subject::find($conflict->subject_id)->name ?? 'Unknown Subject';
                 }
                 
                 $this->conflictDetails = "Already teaching {$cClass} ({$cSubject})";
                 $this->showConflictWarning = true;
                 return; // Stop Save
             }
        }

        if ($this->isSubstitute && $actualSubTeacherId && $this->substituteDate) {
            $subData = [
                'class_id' => $this->modalClassId,
                'subject_id' => $this->selectedSubjectId,
                'teacher_id' => $actualSubTeacherId,
                'schedule_template_id' => $this->selectedTemplateId,
                'day' => $this->selectedDay, // Keeps the day string (e.g. Monday) for reference
                'period_no' => $this->modalPeriodNo,
                'room' => $this->room,
                'is_divided' => false,
                'is_substitute' => true,
                'substitute_date' => $this->substituteDate,
                'start_time' => null,
                'end_time' => null,
                'updated_at' => now(),
            ];
            
            // Robust Upsert for Substitute
            $existingSub = null;
            
            // 1. If editing an existing record, update it directly
            if ($this->editingId) {
                $existingSub = DB::table('timetables')->where('id', $this->editingId)->first();
            }
            
            // 2. Fallback: Check by Unique Constraint (Class+Period+Date) if creating new or lost ID
            if (!$existingSub) {
                $existingSub = DB::table('timetables')
                    ->where('class_id', $this->modalClassId)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('substitute_date', $this->substituteDate)
                    ->where('is_substitute', true)
                    ->where('schedule_template_id', $this->selectedTemplateId)
                    ->first();
            }

            if ($existingSub) {
                // Update existing substitute
                $subData['updated_at'] = now();
                DB::table('timetables')->where('id', $existingSub->id)->update($subData);
            } else {
                // Create NEW substitute record.
                $subData['created_at'] = now();
                DB::table('timetables')->insert($subData);
            }
        }
        
        // Handle REGULAR assignment (Not substitute)
        if (!$this->isSubstitute) {
             // ... existing validation and logic
             // I need to find the main save logic. It's likely above this block.
             // Wait, I am editing the END of the function in previous steps?
             // No, standard save is likely before substitute block.
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
        $currentDayEntries = DB::table('timetables')
            ->where('day', $this->selectedDay)
            ->where('is_substitute', false)
            ->get();

        if ($currentDayEntries->isEmpty()) {
            session()->flash('error', 'No schedule entries to copy for ' . $this->selectedDay);
            return;
        }

        $targetDays = collect($this->days)->filter(fn($d) => $d !== $this->selectedDay);

        foreach ($targetDays as $day) {
            // Delete existing entries for target day
            DB::table('timetables')
                ->where('day', $day)
                ->where('is_substitute', false)
                ->delete();

            // Copy current day entries
            foreach ($currentDayEntries as $entry) {
                DB::table('timetables')->insert([
                    'class_id' => $entry->class_id,
                    'subject_id' => $entry->subject_id,
                    'subject_id_2' => $entry->subject_id_2,
                    'teacher_id' => $entry->teacher_id,
                    'schedule_template_id' => $entry->schedule_template_id,
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
        DB::table('timetables')
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
