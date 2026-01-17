<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Exam;
use App\Models\AcademicSession;
use Livewire\WithPagination;

class ExamManager extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isEditMode = false;

    // Form Fields
    public $examId;
    public $name;
    public $type;
    public $description;
    public $academic_session_id;
    public $start_date;
    public $end_date;
    public $is_active = false;

    // Session Management
    public $selectedSessionId;
    public $academicSessions = [];

    protected $queryString = ['selectedSessionId'];

    protected $rules = [
        'name' => 'required|min:3',
        'type' => 'required|string',
        'description' => 'nullable|string',
        'academic_session_id' => 'required|exists:academic_sessions,id',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->academicSessions = AcademicSession::orderBy('start_date', 'desc')->get();
        
        // Enforce Data Scope: If no permission, lock to active session
        $activeSessionId = $this->academicSessions->where('is_active', true)->first()->id ?? $this->academicSessions->first()->id ?? null;

        if (!auth()->user()->can('exams.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
            $this->selectedSessionId = $activeSessionId;
            // Prevent other sessions from being available in UI logic potentially
            $this->academicSessions = $this->academicSessions->where('id', $activeSessionId);
        } else {
            // Default to active, but allow change
            $this->selectedSessionId = $activeSessionId;
        }
    }
 
    public function updatedSelectedSessionId()
    {
        // Double check permission on update
        if (!auth()->user()->can('exams.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
             $activeSessionId = AcademicSession::where('is_active', true)->value('id');
             if ($this->selectedSessionId != $activeSessionId) {
                 $this->selectedSessionId = $activeSessionId;
                 abort(403, 'Unauthorized scope access');
             }
        }
        $this->resetPage();
    }

    public function render()
    {
        $exams = Exam::query()
            ->with('academicSession')
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        $sessions = AcademicSession::all();

        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.exam-manager', [
            'exams' => $exams,
            'sessions' => $this->academicSessions
        ])->layout($layout, ['title' => 'Exams']);
    }

    public function create()
    {
        $this->authorize('exam.create');

        $this->reset(['examId', 'name', 'type', 'description', 'academic_session_id', 'start_date', 'end_date', 'is_active']);
        $this->selectedClasses = [];  // Explicit initialization as empty array
        $this->availableClasses = \App\Models\Classes::orderBy('numeric_value')->get();
        
        // Auto-select first session if available
        $firstSession = AcademicSession::where('is_active', true)->first();
        if ($firstSession) {
            $this->academic_session_id = $firstSession->id;
        }

        $this->isEditMode = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->authorize('exam.edit');

        $exam = Exam::findOrFail($id);
        $this->examId = $exam->id;
        $this->name = $exam->name;
        $this->type = $exam->type;
        $this->description = $exam->description;
        $this->academic_session_id = $exam->academic_session_id;
        $this->start_date = $exam->start_date; 
        $this->end_date = $exam->end_date;
        $this->is_active = $exam->is_active;

        $this->availableClasses = \App\Models\Classes::orderBy('numeric_value')->get();
        // Load selected classes from existing schedules
        $this->selectedClasses = \App\Models\ExamSchedule::where('exam_id', $exam->id)
            ->pluck('class_id')
            ->unique()
            ->map(fn($id) => (string)$id)
            ->toArray();

        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function store()
    {
        if ($this->isEditMode) {
            $this->authorize('exam.edit');
        } else {
            $this->authorize('exam.create');
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'academic_session_id' => $this->academic_session_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditMode) {
            $exam = Exam::findOrFail($this->examId);
            $exam->update($data);
            $examId = $exam->id;
            session()->flash('message', 'Exam updated successfully.');
        } else {
            $exam = Exam::create($data);
            $examId = $exam->id;
            session()->flash('message', 'Exam created successfully.');
        }

        // Ensure selectedClasses is an array
        $selectedClassIds = is_array($this->selectedClasses) 
            ? array_map('intval', $this->selectedClasses) 
            : [];

        // SYNC Classes: Remove classes not in selectedClasses
        if ($this->isEditMode && !empty($selectedClassIds)) {
            \App\Models\ExamSchedule::where('exam_id', $examId)
                ->whereNotIn('class_id', $selectedClassIds)
                ->delete();
        }

        // Add new class schedules for selected classes
        foreach ($selectedClassIds as $classId) {
            $subjects = \App\Models\Subject::where('class_id', $classId)->get();
            foreach ($subjects as $subject) {
                 \App\Models\ExamSchedule::firstOrCreate(
                    [
                        'exam_id' => $examId,
                        'class_id' => $classId,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'max_marks' => 100, // Default
                    ]
                );
            }
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $this->authorize('exam.delete');
        Exam::findOrFail($id)->delete();
        session()->flash('message', 'Exam deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $this->authorize('exam.edit');
        $exam = Exam::findOrFail($id);
        $exam->update(['is_active' => !$exam->is_active]);
    }

    // -- Hub / Datesheet Logic --
    public $manageExamId;
    public $isManageModalOpen = false;
    public $manageExamName;
    
    // Datesheet State
    public $datesheetDates = []; // List of date strings Y-m-d
    // Structure: [date_string][class_id] => subject_id (nullable)
    public $datesheetData = []; 
    public $availableClasses = [];
    public $selectedClasses = []; 
    public $datesheetFilterClasses = []; // Decoupled from selectedClasses for Hub filtering
    public $classSubjectsMap = []; // [class_id => Collection of Subjects]

    public $scheduledSubjects = []; // [class_id => [subject_id, subject_id...]]

    // Explicit toggle method for class selection (matches React pattern)
    public function toggleClass($classId)
    {
        // Always ensure selectedClasses is an array
        if (!is_array($this->selectedClasses)) {
            $this->selectedClasses = [];
        }
        
        $classId = (string)$classId;
        $key = array_search($classId, $this->selectedClasses, true);
        
        if ($key !== false) {
            // Remove the class
            unset($this->selectedClasses[$key]);
            $this->selectedClasses = array_values($this->selectedClasses);
        } else {
            // Add the class
            $this->selectedClasses[] = $classId;
        }
        
        // Force property update for Livewire
        $this->selectedClasses = $this->selectedClasses;
    }

    // Generator inputs
    public $genStartDate;
    public $genEndDate;
    public $genSingleDate;

    public function manageExam($id)
    {
        $this->manageExamId = $id;
        $exam = Exam::findOrFail($id);
        $this->manageExamName = $exam->name;
        
        $this->availableClasses = \App\Models\Classes::orderBy('numeric_value')->get();
        // Load selected classes based on existing schedules or defaults
        $existingSchedules = \App\Models\ExamSchedule::where('exam_id', $id)->get();
        // Initialize Filter with ALL classes that have schedules, or all available if none?
        // Actually, for the Hub, we might want to start with the ones that are relevant.
        // Let's copy the logic but use the new property.
        $this->datesheetFilterClasses = $existingSchedules->pluck('class_id')->unique()->toArray();
        $this->datesheetFilterClasses = array_map('strval', $this->datesheetFilterClasses);  

        // Load existing Date Rows
        $this->datesheetDates = $existingSchedules->pluck('exam_date')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Populate Data Map and Scheduled Subjects Cache
        $this->datesheetData = [];
        $this->scheduledSubjects = [];
        
        foreach ($existingSchedules as $sched) {
            if ($sched->exam_date) {
                // Map for Pivot
                $this->datesheetData[$sched->exam_date][$sched->class_id] = $sched->subject_id;
                
                // Track scheduled subject
                // Check if subject is already in the list for this class
                if (!isset($this->scheduledSubjects[$sched->class_id])) {
                    $this->scheduledSubjects[$sched->class_id] = [];
                }
                if (!in_array($sched->subject_id, $this->scheduledSubjects[$sched->class_id])) {
                    $this->scheduledSubjects[$sched->class_id][] = $sched->subject_id;
                }
            }
        }
        
        $this->isManageModalOpen = true;
    }

    public function closeManageModal()
    {
        $this->isManageModalOpen = false;
        $this->isManageModalOpen = false;
        $this->reset(['manageExamId', 'datesheetDates', 'datesheetData', 'datesheetFilterClasses', 'genStartDate', 'genEndDate', 'genSingleDate']);
    }

    public function updatedSelectedClasses()
    {
        // When classes change, we might need to load their subjects?
        // View helper will handle it, or we pre-load map to reduce queries in loop
    }

    public function getSubjectsForClass($classId)
    {
        if (!isset($this->classSubjectsMap[$classId])) {
            $this->classSubjectsMap[$classId] = \App\Models\Subject::where('class_id', $classId)->get();
        }
        return $this->classSubjectsMap[$classId];
    }

    public function addDateRange()
    {
        $this->validate([
            'genStartDate' => 'required|date',
            'genEndDate' => 'required|date|after_or_equal:genStartDate',
        ]);

        $period = \Carbon\CarbonPeriod::create($this->genStartDate, $this->genEndDate);
        foreach ($period as $date) {
            $d = $date->format('Y-m-d');
            if (!in_array($d, $this->datesheetDates)) {
                $this->datesheetDates[] = $d;
            }
        }
        sort($this->datesheetDates);
    }

    public function addSingleDate()
    {
        $this->validate(['genSingleDate' => 'required|date']);
        if (!in_array($this->genSingleDate, $this->datesheetDates)) {
            $this->datesheetDates[] = $this->genSingleDate;
            sort($this->datesheetDates);
        }
    }

    public function removeDateRow($date)
    {
        // 1. Remove from UI array
        $this->datesheetDates = array_values(array_diff($this->datesheetDates, [$date]));
        
        // 2. Clear from DB? Reference project usually implies "Delete Row" wipes the schedule for that day.
        // Let's do it safely: Unset dates for schedules on this day for this exam.
        \App\Models\ExamSchedule::where('exam_id', $this->manageExamId)
            ->where('exam_date', $date)
            ->update(['exam_date' => null]);
            
        unset($this->datesheetData[$date]);
    }

    // Called when a Subject is selected in the Pivot Table
    public function updateSchedule($date, $classId, $subjectId)
    {
        // 1. Identify old subject (if any) to remove from scheduled list
        // Since we don't have direct old ID here easily without DB query or keeping more state
        // We will just re-fetch the scheduled list for this class after update, or do manual management
        // Simple manual management:
        $oldSubjectId = $this->datesheetData[$date][$classId] ?? null;
        
        // Remove old subject from scheduled list
        if ($oldSubjectId && isset($this->scheduledSubjects[$classId])) {
            $this->scheduledSubjects[$classId] = array_diff($this->scheduledSubjects[$classId], [$oldSubjectId]);
        }

        // 2. Clear collision in DB
        \App\Models\ExamSchedule::where('exam_id', $this->manageExamId)
            ->where('class_id', $classId)
            ->where('exam_date', $date)
            ->update(['exam_date' => null]);
            
        // 3. Update DB
        if (!empty($subjectId)) {
            \App\Models\ExamSchedule::updateOrCreate(
                [
                    'exam_id' => $this->manageExamId,
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                ],
                [
                    'exam_date' => $date,
                    'max_marks' => 100 // Default if creating
                ]
            );
            
            // Add new subject to scheduled list
            if (!isset($this->scheduledSubjects[$classId])) {
                $this->scheduledSubjects[$classId] = [];
            }
            $this->scheduledSubjects[$classId][] = $subjectId;
        }
        
        // 4. Update UI State
        $this->datesheetData[$date][$classId] = $subjectId;
    }

    // -- Marks Configuration Modal Logic --
    public $isConfigModalOpen = false;
    public $configureExamId;
    public $configureExamName;
    public $marksConfigData = []; // [class_id => [subject_name => [total_marks, passing_marks]]]

    public function openConfigModal($examId)
    {
        $this->configureExamId = $examId;
        $exam = Exam::findOrFail($examId);
        $this->configureExamName = $exam->name;
        
        $this->availableClasses = \App\Models\Classes::orderBy('numeric_value')->get();
        
        // Load existing marks configuration from marks_configs table
        $existingConfigs = \App\Models\MarksConfig::where('exam_id', $examId)->get();
        
        // Get selected classes from existing configs OR from exam_schedules
        $configClassIds = $existingConfigs->pluck('class_id')->unique()->toArray();
        if (empty($configClassIds)) {
            // Fall back to exam_schedules for backward compatibility
            $configClassIds = \App\Models\ExamSchedule::where('exam_id', $examId)
                ->pluck('class_id')
                ->unique()
                ->toArray();
        }
        $this->selectedClasses = array_map('strval', $configClassIds);

        // Load marks config data
        $this->marksConfigData = [];
        foreach ($existingConfigs as $config) {
            $this->marksConfigData[$config->class_id][$config->subject] = [
                'total_marks' => $config->total_marks,
                'passing_marks' => $config->passing_marks,
            ];
        }

        $this->isConfigModalOpen = true;
    }

    public function closeConfigModal()
    {
        $this->isConfigModalOpen = false;
        $this->reset(['configureExamId', 'marksConfigData']);
    }

    /**
     * Auto-fill subjects from class management
     */
    public function autoFillSubjects($classId)
    {
        $classId = (int)$classId;
        $subjects = $this->getSubjectsForClass($classId);
        
        if (!isset($this->marksConfigData[$classId])) {
            $this->marksConfigData[$classId] = [];
        }
        
        $addedCount = 0;
        foreach ($subjects as $subject) {
            if (!isset($this->marksConfigData[$classId][$subject->name])) {
                $this->marksConfigData[$classId][$subject->name] = [
                    'total_marks' => 100,
                    'passing_marks' => 33,
                ];
                $addedCount++;
            }
        }
        
        if ($addedCount > 0) {
            session()->flash('message', "Added {$addedCount} subjects from class definition.");
        } else {
            session()->flash('message', 'All class subjects are already configured.');
        }
    }

    public function saveConfig()
    {
        if (!$this->configureExamId) return;

        foreach ($this->selectedClasses as $classId) {
            $classId = (int)$classId;
            
            $classConfigs = $this->marksConfigData[$classId] ?? [];
            
            foreach ($classConfigs as $subjectName => $data) {
                \App\Models\MarksConfig::updateOrCreate(
                    [
                        'exam_id' => $this->configureExamId,
                        'class_id' => $classId,
                        'subject' => $subjectName,
                    ],
                    [
                        'total_marks' => $data['total_marks'] ?? 100,
                        'passing_marks' => $data['passing_marks'] ?? 33,
                    ]
                );
            }
        }
        
        session()->flash('message', 'Marks configuration saved successfully.');
        $this->closeConfigModal();
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->reset(['examId', 'name', 'type', 'description', 'academic_session_id', 'start_date', 'end_date', 'is_active', 'selectedClasses']);
    }
}
