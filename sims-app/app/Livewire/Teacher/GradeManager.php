<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\Classes;

class GradeManager extends Component
{
    // Selection
    public $selectedExamId;
    public $selectedClassId;
    public $selectedSubjectId;

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
    public $passingScore = 33; // Calculated: maxMarks * (passingMarks / 100)
    public $isGradingAllowed = true;
    public $isLocked = false;

    public function mount()
    {
        $this->loadExams();
    }

    public function loadExams()
    {
        $user = Auth::user();
        
        // Get teacher's assigned class IDs
        $teacherClassIds = $this->getTeacherClassIds();
        
        if (empty($teacherClassIds)) {
            $this->exams = collect([]);
            return;
        }
        
        // Show exams that include at least one of teacher's classes
        $examIdsWithTeacherClasses = DB::table('exam_schedules')
            ->whereIn('class_id', $teacherClassIds)
            ->pluck('exam_id')
            ->unique()
            ->toArray();

        // Show all exams (not just active), teacher can see completed exams too
        $this->exams = Exam::whereIn('id', $examIdsWithTeacherClasses)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($this->exams->isNotEmpty()) {
            $this->selectedExamId = $this->exams->first()->id;
            $this->loadAvailableClasses();
        }
    }

    /**
     * Get all class IDs the teacher can access (class teacher + subject allocations)
     */
    private function getTeacherClassIds(): array
    {
        $user = Auth::user();
        $classIds = [];

        // Class Teacher's own class
        if ($user->class_id) {
            $classIds[] = $user->class_id;
        }

        // Subject allocations
        $allocatedClassIds = DB::table('subject_allocations')
            ->where('user_id', $user->id)
            ->pluck('class_id')
            ->toArray();

        return array_unique(array_merge($classIds, $allocatedClassIds));
    }

    public function updatedSelectedExamId()
    {
        $this->loadAvailableClasses();
        $this->reset(['selectedClassId', 'selectedSubjectId', 'students', 'grades']);
        
        // Check if grading is allowed for this exam
        $exam = Exam::find($this->selectedExamId);
        $this->isGradingAllowed = !($exam && $exam->status === 'Upcoming');
    }

    public function loadAvailableClasses()
    {
        if (!$this->selectedExamId) {
            $this->availableClasses = collect([]);
            return;
        }

        $user = Auth::user();
        
        // Get classes assigned to this exam
        $examClassIds = DB::table('exam_schedules')
            ->where('exam_id', $this->selectedExamId)
            ->pluck('class_id')
            ->unique()
            ->toArray();

        // Get teacher's class IDs
        $teacherClassIds = $this->getTeacherClassIds();

        // Intersection: classes that are in BOTH exam AND teacher's assignments
        $validClassIds = array_intersect($examClassIds, $teacherClassIds);

        $this->availableClasses = Classes::whereIn('id', $validClassIds)
            ->orderBy('numeric_value')
            ->get();

        // Auto-select first class, prioritizing class teacher's own class
        if ($this->availableClasses->isNotEmpty()) {
            if ($user->class_id && in_array($user->class_id, $validClassIds)) {
                $this->selectedClassId = $user->class_id;
            } else {
                $this->selectedClassId = $this->availableClasses->first()->id;
            }
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

        $user = Auth::user();
        
        // Get subjects configured for this exam+class
        $configuredSubjects = DB::table('marks_configs')
            ->where('exam_id', $this->selectedExamId)
            ->where('class_id', $this->selectedClassId)
            ->pluck('subject')
            ->toArray();

        // Build list of subject names/IDs the teacher can grade
        $allowedSubjectNames = [];
        $allowedSubjectIds = [];
        
        // 1. If class teacher for this class, add their class_subject
        if ($user->class_id == $this->selectedClassId && $user->class_subject) {
            $allowedSubjectNames[] = $user->class_subject;
        }
        
        // 2. Also add subjects from subject_allocations (works for both class teacher and subject teacher)
        $allocatedSubjects = DB::table('subject_allocations')
            ->where('user_id', $user->id)
            ->where('class_id', $this->selectedClassId)
            ->pluck('subject_id')
            ->toArray();
        
        $allowedSubjectIds = array_merge($allowedSubjectIds, $allocatedSubjects);
        
        // Build query: subjects that match by name OR by ID, and are configured for this exam
        $query = Subject::where('class_id', $this->selectedClassId)
            ->whereIn('name', $configuredSubjects);
        
        if (!empty($allowedSubjectNames) && !empty($allowedSubjectIds)) {
            // Class teacher with allocations: match by name OR id
            $query->where(function($q) use ($allowedSubjectNames, $allowedSubjectIds) {
                $q->whereIn('name', $allowedSubjectNames)
                  ->orWhereIn('id', $allowedSubjectIds);
            });
        } elseif (!empty($allowedSubjectNames)) {
            // Class teacher without extra allocations
            $query->whereIn('name', $allowedSubjectNames);
        } elseif (!empty($allowedSubjectIds)) {
            // Subject teacher only
            $query->whereIn('id', $allowedSubjectIds);
        } else {
            // No permissions at all
            $this->subjects = collect([]);
            return;
        }
        
        $this->subjects = $query->get();

        // Auto-select first subject
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

        // Get subject name for marks_config lookup
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

        // Check Lock Status
        // Check Lock Status (Exam Specific)
        $schedule = DB::table('exam_schedules')
            ->where('exam_id', $this->selectedExamId)
            ->where('class_id', $this->selectedClassId)
            ->where('subject_id', $this->selectedSubjectId)
            ->first();

        $isExamLocked = $schedule ? (bool)$schedule->is_locked : false;

        // Check Lock Status (Admin Global Lock for this User/Class/Subject)
        $isAdminLocked = DB::table('grade_locks')
            ->where('user_id', Auth::id())
            ->where('class_id', $this->selectedClassId)
            ->where('subject_id', $this->selectedSubjectId)
            ->exists();

        $this->isLocked = $isExamLocked || $isAdminLocked;

        // Fetch Students
        $this->students = DB::table('students')
            ->where('class_id', $this->selectedClassId)
            ->orderBy('roll_no')
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
        
        // Initialize empty for students without marks
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

        if ($this->isLocked) {
            session()->flash('error', 'This gradebook has been locked and cannot be edited.');
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
                
                // Skip if neither marks entered nor marked absent
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

    public function lockGrades()
    {
        if (!$this->selectedExamId || !$this->selectedClassId || !$this->selectedSubjectId) return;

        DB::table('exam_schedules')->updateOrInsert(
            [
                'exam_id' => $this->selectedExamId,
                'class_id' => $this->selectedClassId,
                'subject_id' => $this->selectedSubjectId
            ],
            [
                'is_locked' => true,
                'updated_at' => now()
            ]
        );

        $this->isLocked = true;
        session()->flash('message', 'Gradebook locked successfully.');
    }

    public function unlockGrades()
    {
        if (!auth()->user()->can('allocations.lock')) {
            session()->flash('error', 'You do not have permission to unlock gradebooks.');
            return;
        }

        if (!$this->selectedExamId || !$this->selectedClassId || !$this->selectedSubjectId) return;

        DB::table('exam_schedules')->updateOrInsert(
            [
                'exam_id' => $this->selectedExamId,
                'class_id' => $this->selectedClassId,
                'subject_id' => $this->selectedSubjectId
            ],
            [
                'is_locked' => false,
                'updated_at' => now()
            ]
        );

        // Also remove Admin lock if exists, to ensure full unlock
        DB::table('grade_locks')
            ->where('class_id', $this->selectedClassId)
            ->where('subject_id', $this->selectedSubjectId)
            ->where('user_id', Auth::id()) // Only unlock for self if viewing as self
            ->delete();

        $this->isLocked = false;
        session()->flash('message', 'Gradebook unlocked.');
    }

    public function render()
    {
        $user = Auth::user();
        
        return view('livewire.teacher.grade-manager', [
            'isClassTeacher' => $this->selectedClassId && $user->class_id == $this->selectedClassId,
        ])->layout('components.layouts.teacher', ['title' => 'Gradebook']);
    }
}
