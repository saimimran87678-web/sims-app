<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\Classes;

class GradeManager extends Component
{
    // Selection
    public $selectedExamId;
    public $selectedClassId;
    public $selectedSubjectId;

    // Session Management
    public $selectedSessionId;
    public $academicSessions = [];

    protected $queryString = ['selectedSessionId'];

    // Data Lists
    public $exams = [];
    public $availableClasses = [];
    public $subjects = [];
    public $students = [];
    
    // Input State
    public $grades = []; // [student_id => marks]
    public $absents = []; // [student_id => bool] - true if absent
    public $maxMarks = 100;
    public $passingMarks = 33; // Percentage
    public $passingScore = 33; // Calculated
    public $isGradingAllowed = true;

    public function mount()
    {
        $this->authorize('exams.manage');
        
        $this->academicSessions = \App\Models\AcademicSession::orderBy('start_date', 'desc')->get();
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        // Enforce Data Scope
        // Note: GradeManager is heavily tied to Exams, so we check 'grades.view-sessions' OR 'exams.view-sessions' if grades doesn't exist, but we made grades.view-sessions
        if (!auth()->user()->can('grades.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
            $this->selectedSessionId = $activeSessionId;
            $this->academicSessions = $this->academicSessions->where('id', $activeSessionId);
        } else {
            $this->selectedSessionId = $activeSessionId;
        }

        $this->loadExams();
    }

    public function updatedSelectedSessionId()
    {
        $this->loadExams();
        $this->reset(['selectedExamId', 'selectedClassId', 'selectedSubjectId', 'students', 'grades', 'subjects']);
    }

    public function loadExams()
    {
        if ($this->selectedSessionId) {
            $this->exams = Exam::where('academic_session_id', $this->selectedSessionId)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $this->exams = collect();
        }

        if ($this->exams->isNotEmpty()) {
            $this->selectedExamId = $this->exams->first()->id;
            $this->loadAvailableClasses();
        } else {
            $this->selectedExamId = null;
            $this->availableClasses = [];
        }
    }

    public function updatedSelectedExamId()
    {
        $this->loadAvailableClasses();
        $this->reset(['selectedClassId', 'selectedSubjectId', 'students', 'grades', 'subjects']);
        
        $exam = Exam::find($this->selectedExamId);
        $this->isGradingAllowed = !($exam && $exam->status === 'Upcoming');
    }

    public function loadAvailableClasses()
    {
        if (!$this->selectedExamId) {
            $this->availableClasses = collect([]);
            return;
        }

        // Get classes assigned to this exam
        $examClassIds = DB::table('exam_schedules')
            ->where('exam_id', $this->selectedExamId)
            ->pluck('class_id')
            ->unique()
            ->toArray();

        $this->availableClasses = Classes::withoutGlobalScope('active_session')
            ->whereIn('id', $examClassIds)
            ->orderBy('numeric_value')
            ->get();

        if ($this->availableClasses->isNotEmpty()) {
            $this->selectedClassId = $this->availableClasses->first()->id;
            $this->loadSubjects();
        }
    }

    public function updatedSelectedClassId()
    {
        $this->loadSubjects();
        $this->reset(['selectedSubjectId', 'students', 'grades']);
    }

    public function loadSubjects()
    {
        if (!$this->selectedClassId || !$this->selectedExamId) {
            $this->subjects = [];
            return;
        }

        // Get subjects configured for this exam+class
        $configuredSubjects = DB::table('marks_configs')
            ->where('exam_id', $this->selectedExamId)
            ->where('class_id', $this->selectedClassId)
            ->pluck('subject')
            ->toArray();

        $this->subjects = Subject::where('class_id', $this->selectedClassId)
            ->whereIn('name', $configuredSubjects)
            ->get();

        if ($this->subjects->isNotEmpty()) {
            $this->selectedSubjectId = $this->subjects->first()->id;
            $this->loadStudentsAndGrades();
        }
    }

    public function updatedSelectedSubjectId()
    {
        $this->loadStudentsAndGrades();
    }

    public function loadStudentsAndGrades()
    {
        if (!$this->selectedExamId || !$this->selectedClassId || !$this->selectedSubjectId) {
            return;
        }

        $subject = Subject::find($this->selectedSubjectId);
        if (!$subject) return;

        // Fetch max marks and passing marks from marks_configs
        $marksConfig = DB::table('marks_configs')
            ->where('exam_id', $this->selectedExamId)
            ->where('class_id', $this->selectedClassId)
            ->where('subject', $subject->name)
            ->first();

        $this->maxMarks = $marksConfig->total_marks ?? 100;
        $this->passingMarks = $marksConfig->passing_marks ?? 33;
        $this->passingScore = ($this->maxMarks * $this->passingMarks) / 100;

        // Fetch Students (Admins see all active students in the class)
        $this->students = \App\Models\Student::where('class_id', $this->selectedClassId)
            ->where('status', 'active')
            ->orderByRaw('CAST(roll_no AS INTEGER) ASC')
            ->get();

        // Fetch Existing Marks
        $existingMarks = DB::table('exam_marks')
            ->where('exam_id', $this->selectedExamId)
            ->where('subject_id', $this->selectedSubjectId)
            ->whereIn('student_id', $this->students->pluck('id'))
            ->get();

        // Map to State
        $this->grades = [];
        $this->absents = [];
        foreach ($existingMarks as $mark) {
            if ($mark->is_absent) {
                $this->absents[$mark->student_id] = true;
                $this->grades[$mark->student_id] = '';
            } else {
                $this->grades[$mark->student_id] = (float) $mark->marks_obtained;
                $this->absents[$mark->student_id] = false;
            }
        }
        
        foreach ($this->students as $student) {
            if (!isset($this->grades[$student->id])) {
                $this->grades[$student->id] = '';
                $this->absents[$student->id] = false;
            }
        }
    }

    public function save()
    {
        $exam = Exam::find($this->selectedExamId);
        if ($exam && $exam->status === 'Upcoming') {
             session()->flash('error', 'Grading is not allowed for Upcoming exams.');
             return;
        }

        $this->validate([
            'selectedExamId' => 'required',
            'selectedClassId' => 'required',
            'selectedSubjectId' => 'required',
            'grades.*' => 'nullable|numeric|min:0|max:' . $this->maxMarks,
        ]);

        DB::beginTransaction();
        try {
            foreach ($this->students as $student) {
                $studentId = $student->id;
                $marks = $this->grades[$studentId] ?? '';
                $isAbsent = $this->absents[$studentId] ?? false;
                
                if (($marks === '' || $marks === null) && !$isAbsent) continue;

                DB::table('exam_marks')->updateOrInsert(
                    [
                        'exam_id' => $this->selectedExamId,
                        'student_id' => $studentId,
                        'subject_id' => $this->selectedSubjectId,
                    ],
                    [
                        'marks_obtained' => $isAbsent ? 0 : $marks,
                        'is_absent' => $isAbsent,
                        'max_marks' => $this->maxMarks,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
            DB::commit();
            session()->flash('message', 'Grades saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save grades: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.grade-manager')->layout('components.layouts.admin', ['title' => 'Gradebook']);
    }
}
