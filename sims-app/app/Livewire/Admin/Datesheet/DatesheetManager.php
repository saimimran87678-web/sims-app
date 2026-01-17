<?php

namespace App\Livewire\Admin\Datesheet;

use App\Models\Exam;
use App\Models\DatesheetSchedule;
use App\Models\Classes;
use App\Models\MarksConfig;
use Livewire\Component;

class DatesheetManager extends Component
{
    public $examId;
    public $exam;
    
    // UI State
    public $viewMode = 'schedule'; // 'schedule' or 'marks'
    public $showAssignModal = false;
    public $selectedDate = null;
    public $targetClasses = []; 
    public $subjectInput = '';
    public $allClassIds = []; 
    public $visibleClassIds = []; 
    
    // Data Structures
    public $dates = []; // [ '2024-12-01', ... ]
    public $groupedClasses = []; // [ '10' => [classObj, classObj], '9' => ... ]
    
    // Schedule Data
    public $scheduleMatrix = []; // [ date => [ class_id => subject ] ]
    
    // Marks Data
    public $marksMatrix = []; // [class_id => [subject => marks]]
    public $subjectsList = []; // Unique subjects from schedule

    public $startDate = null;
    public $endDate = null;
    public $availableSubjects = [];

    // Inline Editing State
    public $inlineEditDate = null;
    public $inlineEditGrade = null;
    public $gradeSubjects = []; // [grade => [subject1, subject2, ...]]

    public function mount($examId)
    {
        $this->examId = $examId;
        $this->exam = Exam::findOrFail($examId);
        $this->loadData();
    }

    public function loadData()
    {
        // Get class IDs that are assigned to this exam (from exam_schedules)
        $assignedClassIds = \App\Models\ExamSchedule::where('exam_id', $this->examId)
            ->pluck('class_id')
            ->unique()
            ->toArray();

        // If no classes assigned via exam_schedules, fallback to checking datesheet_schedules
        if (empty($assignedClassIds)) {
            $assignedClassIds = DatesheetSchedule::where('exam_id', $this->examId)
                ->pluck('class_id')
                ->unique()
                ->toArray();
        }

        // If still no classes, load all (for new exams)
        if (empty($assignedClassIds)) {
            $allClasses = Classes::orderBy('numeric_value', 'desc')->get();
        } else {
            $allClasses = Classes::whereIn('id', $assignedClassIds)
                ->orderBy('numeric_value', 'desc')
                ->get();
        }

        $this->allClassIds = $allClasses->pluck('id')->toArray();
        
        $this->groupedClasses = $allClasses->groupBy(function ($class) {
            return $class->numeric_value ?? intval(preg_replace('/[^0-9]+/', '', $class->name), 10);
        })->sortKeysDesc()->map(fn($group) => $group->values()->all())->all();

        if (empty($this->visibleClassIds)) {
            $this->visibleClassIds = $this->allClassIds;
        }

        // Fetch Subjects (only from assigned classes)
        $this->availableSubjects = \App\Models\Subject::whereIn('class_id', $this->allClassIds)
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();

        // Build gradeSubjects: For each grade, get all subjects from all sections (deduplicated)
        $this->gradeSubjects = [];
        foreach ($this->groupedClasses as $grade => $classes) {
            $classIds = collect($classes)->pluck('id')->toArray();
            $subjects = \App\Models\Subject::whereIn('class_id', $classIds)
                ->orderBy('name')
                ->pluck('name')
                ->unique()
                ->values()
                ->toArray();
            $this->gradeSubjects[$grade] = $subjects;
        }

        // Data for both modes
        $this->loadSchedule();
        if ($this->viewMode === 'marks') {
            $this->loadMarks();
        }
    }

    public function toggleGrade($grade)
    {
        // Get all class IDs for this grade
        $gradeClasses = collect($this->groupedClasses[$grade] ?? [])->pluck('id')->toArray();
        
        // Check if all are currently selected
        $allSelected = !array_diff($gradeClasses, $this->visibleClassIds);

        if ($allSelected) {
            // Deselect all
            $this->visibleClassIds = array_diff($this->visibleClassIds, $gradeClasses);
        } else {
            // Select all
            $this->visibleClassIds = array_unique(array_merge($this->visibleClassIds, $gradeClasses));
        }
        
        // Re-index array just in case
        $this->visibleClassIds = array_values($this->visibleClassIds);
    }

    public $classSubjects = []; // [class_id => [subject1, subject2]]

    public function loadSchedule()
    {
        $schedules = DatesheetSchedule::where('exam_id', $this->examId)->get();

        $dbDates = $schedules->pluck('exam_date')->unique()->values()->toArray();
        $this->dates = array_unique(array_merge($this->dates, $dbDates));
        sort($this->dates);

        $subjects = [];
        $this->classSubjects = []; // Reset

        foreach ($schedules as $sch) {
            $this->scheduleMatrix[$sch->exam_date][$sch->class_id] = $sch->subject;
            
            if ($sch->subject && $sch->subject !== 'Holiday' && $sch->subject !== '-') {
                foreach (explode('/', $sch->subject) as $s) {
                    $s = trim($s);
                    if ($s) {
                        $subjects[] = $s;
                        // Track subject for this class
                        $this->classSubjects[$sch->class_id][] = $s;
                    }
                }
            }
        }
        
        // Also ensure array unique for class subjects
        foreach ($this->classSubjects as $cid => $subs) {
            $this->classSubjects[$cid] = array_unique($subs);
        }
        
        // Also merge availableSubjects into subjectsList for marks config defaults
        if (empty($subjects) && !empty($this->availableSubjects)) {
            $subjects = $this->availableSubjects;
        } else {
            $subjects = array_unique(array_merge($subjects, $this->availableSubjects));
        }
        
        $this->subjectsList = array_unique($subjects);
        sort($this->subjectsList);
    }

    public $newSubjectNames = []; // [class_id => 'Subject Name']

    public function loadMarks()
    {
        // Load existing configs with both total and passing marks
        $configs = MarksConfig::where('exam_id', $this->examId)->get();
        foreach ($configs as $cfg) {
            $this->marksMatrix[$cfg->class_id][$cfg->subject] = [
                'total' => $cfg->total_marks ?? 75,
                'passing' => $cfg->passing_marks ?? 33
            ];
        }
    }

    public function addSubject($classId)
    {
        $name = trim($this->newSubjectNames[$classId] ?? '');
        
        if (empty($name)) {
            $name = trim($this->newSubjectNames[intval($classId)] ?? '');
        }

        if (!$name) return;

        // 1. Ensure Subject exists in Global Subject Table
        $subject = \App\Models\Subject::where('class_id', $classId)
            ->where('name', $name)
            ->first();

        if (!$subject) {
            $subject = new \App\Models\Subject();
            $subject->class_id = $classId;
            $subject->name = $name;
            $subject->code = strtoupper(substr($name, 0, 3));
            $subject->save();
        }

        // 2. Add to Marks Configuration
        $this->marksMatrix[$classId][$name] = ['total' => 75, 'passing' => 33];
        
        MarksConfig::updateOrCreate(
            ['exam_id' => $this->examId, 'class_id' => $classId, 'subject' => $name],
            ['total_marks' => 75, 'passing_marks' => 33]
        );

        // Clear input
        $this->newSubjectNames[$classId] = '';
        unset($this->newSubjectNames[intval($classId)]);
        
        session()->flash('message', 'Subject added successfully.');
    }

    public function removeSubject($classId, $subject)
    {
        if (isset($this->marksMatrix[$classId][$subject])) {
            unset($this->marksMatrix[$classId][$subject]);
            
            // Delete from DB
            MarksConfig::where('exam_id', $this->examId)
                ->where('class_id', $classId)
                ->where('subject', $subject)
                ->delete();
        }
    }

    public function autoFillSubjects($classId)
    {
        // Fetch subjects defined for this class in Global Subject Database
        $subjects = \App\Models\Subject::where('class_id', $classId)
            ->pluck('name')
            ->toArray();
            
        // Add them if missing
        foreach ($subjects as $sub) {
            if (!isset($this->marksMatrix[$classId][$sub])) {
                $this->marksMatrix[$classId][$sub] = ['total' => 75, 'passing' => 33];
                
                MarksConfig::updateOrCreate(
                    ['exam_id' => $this->examId, 'class_id' => $classId, 'subject' => $sub],
                    ['total_marks' => 75, 'passing_marks' => 33]
                );
            }
        }
        
        if (empty($subjects)) {
            session()->flash('message', 'No subjects found for this class in database.');
        } else {
            session()->flash('message', 'Subjects auto-filled from database.');
        }
    }

    public function updateMark($classId, $subject, $field, $value)
    {
        // Update local state
        if (!isset($this->marksMatrix[$classId][$subject])) {
            $this->marksMatrix[$classId][$subject] = ['total' => 75, 'passing' => 33];
        }
        $this->marksMatrix[$classId][$subject][$field] = (int)$value;

        // Map field name to DB column
        $dbColumn = ($field === 'total') ? 'total_marks' : 'passing_marks';

        // Update DB
        MarksConfig::updateOrCreate(
            ['exam_id' => $this->examId, 'class_id' => $classId, 'subject' => $subject],
            [$dbColumn => (int)$value]
        );
    }

    public function saveMarks()
    {
        // Bulk save all marks configuration
        foreach ($this->marksMatrix as $classId => $subjects) {
            foreach ($subjects as $subject => $marks) {
                $totalMarks = is_array($marks) ? ($marks['total'] ?? 75) : $marks;
                $passingMarks = is_array($marks) ? ($marks['passing'] ?? 33) : 33;
                
                MarksConfig::updateOrCreate(
                    [
                        'exam_id' => $this->examId,
                        'class_id' => $classId,
                        'subject' => $subject
                    ],
                    [
                        'total_marks' => (int)$totalMarks,
                        'passing_marks' => (int)$passingMarks
                    ]
                );
            }
        }
        session()->flash('message', 'Marks configuration saved successfully.');
    }

    public function toggleMode($mode)
    {
        $this->viewMode = $mode;
        if ($mode === 'marks') {
            $this->loadMarks();
        }
    }

    public function openAssignModal($date, $classId)
    {
        $this->selectedDate = $date;
        $this->targetClasses = [$classId]; // Select clicked class by default
        
        // Pre-fill subject if exists
        $this->subjectInput = $this->scheduleMatrix[$date][$classId] ?? '';
        
        $this->showAssignModal = true;
    }

    public function saveAssignment()
    {
        if (!$this->selectedDate || empty($this->targetClasses)) {
            return;
        }

        foreach ($this->targetClasses as $classId) {
            DatesheetSchedule::updateOrCreate(
                [
                    'exam_id' => $this->examId,
                    'class_id' => $classId,
                    'exam_date' => $this->selectedDate,
                ],
                [
                    'subject' => $this->subjectInput
                ]
            );
            
            $this->scheduleMatrix[$this->selectedDate][$classId] = $this->subjectInput;
        }

        $this->showAssignModal = false;
        $this->loadSchedule(); // Refresh subjects list
    }

    public function addDateRange()
    {
        if (!$this->startDate) {
            return;
        }

        // If only start date is provided, add single date
        if (!$this->endDate) {
            $this->addDate($this->startDate);
            $this->startDate = null; 
            return;
        }

        // Add Range
        try {
            $period = \Carbon\CarbonPeriod::create($this->startDate, $this->endDate);
            foreach ($period as $date) {
                $d = $date->format('Y-m-d');
                $this->addDate($d);
            }
            $this->startDate = null;
            $this->endDate = null;
        } catch (\Exception $e) {
            // Invalid date range
        }
    }

    public function addDate($date)
    {
        if (!in_array($date, $this->dates)) {
            $this->dates[] = $date;
            sort($this->dates);
        }

        // Persist to DB: Create placeholder entries for all classes
        // This ensures the date "exists" even without subjects assigned
        foreach ($this->allClassIds as $classId) {
            DatesheetSchedule::firstOrCreate(
                [
                    'exam_id' => $this->examId,
                    'class_id' => $classId,
                    'exam_date' => $date,
                ],
                [
                    'subject' => '-' // Default placeholder
                ]
            );
        }
    }

    public function deleteDate($date)
    {
        // Remove from DB
        DatesheetSchedule::where('exam_id', $this->examId)
            ->where('exam_date', $date)
            ->delete();

        // Remove from local state
        $this->dates = array_values(array_diff($this->dates, [$date]));
        
        // Remove from matrix
        if (isset($this->scheduleMatrix[$date])) {
            unset($this->scheduleMatrix[$date]);
        }
    }

    // ==================== INLINE EDITING METHODS ====================

    /**
     * Open inline editor for a specific date and grade
     */
    public function openInlineEditor($date, $grade)
    {
        $this->inlineEditDate = $date;
        $this->inlineEditGrade = $grade;
    }

    /**
     * Close inline editor
     */
    public function closeInlineEditor()
    {
        $this->inlineEditDate = null;
        $this->inlineEditGrade = null;
    }

    /**
     * Assign a subject to all classes of a grade for a specific date
     */
    public function assignSubjectInline($subject)
    {
        if (!$this->inlineEditDate || !$this->inlineEditGrade) {
            return;
        }

        $date = $this->inlineEditDate;
        $grade = $this->inlineEditGrade;
        
        // Get all class IDs for this grade
        $classIds = collect($this->groupedClasses[$grade] ?? [])->pluck('id')->toArray();

        foreach ($classIds as $classId) {
            // Get current subject(s)
            $current = $this->scheduleMatrix[$date][$classId] ?? '-';
            
            // If current is empty or placeholder, replace
            if ($current === '-' || $current === '' || $current === 'Holiday') {
                $new = $subject;
            } else {
                // Append to existing (comma-separated), avoiding duplicates
                $existing = array_map('trim', explode(',', $current));
                if (!in_array($subject, $existing)) {
                    $existing[] = $subject;
                }
                $new = implode(', ', $existing);
            }

            // Save to DB
            DatesheetSchedule::updateOrCreate(
                [
                    'exam_id' => $this->examId,
                    'class_id' => $classId,
                    'exam_date' => $date,
                ],
                [
                    'subject' => $new
                ]
            );

            $this->scheduleMatrix[$date][$classId] = $new;
        }

        $this->closeInlineEditor();
    }

    /**
     * Remove a specific subject from a grade's assignment
     */
    public function removeSubjectFromGrade($date, $grade, $subjectToRemove)
    {
        $classIds = collect($this->groupedClasses[$grade] ?? [])->pluck('id')->toArray();

        foreach ($classIds as $classId) {
            $current = $this->scheduleMatrix[$date][$classId] ?? '-';
            
            if ($current === '-' || $current === '' || $current === 'Holiday') {
                continue;
            }

            $subjects = array_map('trim', explode(',', $current));
            $subjects = array_filter($subjects, fn($s) => $s !== $subjectToRemove);
            
            $new = empty($subjects) ? '-' : implode(', ', $subjects);

            DatesheetSchedule::updateOrCreate(
                [
                    'exam_id' => $this->examId,
                    'class_id' => $classId,
                    'exam_date' => $date,
                ],
                ['subject' => $new]
            );

            $this->scheduleMatrix[$date][$classId] = $new;
        }
    }

    /**
     * Mark a date as holiday for all classes of a grade
     */
    public function markHoliday($date, $grade)
    {
        $classIds = collect($this->groupedClasses[$grade] ?? [])->pluck('id')->toArray();

        foreach ($classIds as $classId) {
            DatesheetSchedule::updateOrCreate(
                [
                    'exam_id' => $this->examId,
                    'class_id' => $classId,
                    'exam_date' => $date,
                ],
                ['subject' => 'Holiday']
            );

            $this->scheduleMatrix[$date][$classId] = 'Holiday';
        }

        $this->closeInlineEditor();
    }

    /**
     * Clear assignment for all classes of a grade
     */
    public function clearAssignment($date, $grade)
    {
        $classIds = collect($this->groupedClasses[$grade] ?? [])->pluck('id')->toArray();

        foreach ($classIds as $classId) {
            DatesheetSchedule::updateOrCreate(
                [
                    'exam_id' => $this->examId,
                    'class_id' => $classId,
                    'exam_date' => $date,
                ],
                ['subject' => '-']
            );

            $this->scheduleMatrix[$date][$classId] = '-';
        }

        $this->closeInlineEditor();
    }

    /**
     * Get subjects already assigned to a grade on a date
     */
    public function getAssignedSubjects($date, $grade)
    {
        $classIds = collect($this->groupedClasses[$grade] ?? [])->pluck('id')->toArray();
        $assigned = [];

        foreach ($classIds as $classId) {
            $current = $this->scheduleMatrix[$date][$classId] ?? '-';
            if ($current && $current !== '-' && $current !== 'Holiday') {
                foreach (explode(',', $current) as $s) {
                    $s = trim($s);
                    if ($s) $assigned[] = $s;
                }
            }
        }

        return array_unique($assigned);
    }

    public function render()
    {
        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.datesheet.datesheet-manager')->layout($layout, ['title' => 'Datesheet']);
    }
}
