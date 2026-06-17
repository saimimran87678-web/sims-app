<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use App\Models\Exam;
use App\Models\Classes;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResultReport extends Component
{
    public $exams = [];
    public $classId;
    public $className = '';
    public $examName = '';
    
    public $selectedExamId = '';
    
    public $reportData = [];
    public $columnHeaders = []; // subject_id => name
    public $subjectMaxMarks = []; // subject_id => max_marks
    public $subjectPassingMarks = []; // subject_id => passing percentage
    public $isLoading = false;

    // New properties for class/session selection
    public $classes = [];
    public $selectedClass;
    public $sessions = [];
    public $selectedSession;

    public function updatedSelectedClass()
    {
        $this->classId = $this->selectedClass;
        $this->className = Classes::find($this->classId)->name ?? '';
        
        $this->loadExams();
    }

    public function loadExams()
    {
        if (!$this->classId) {
            $this->exams = collect();
            return;
        }

        $examIds = DB::table('exam_schedules')
            ->where('class_id', $this->classId)
            ->pluck('exam_id')
            ->unique()
            ->toArray();
        
        $this->exams = Exam::whereIn('id', $examIds)
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($this->exams->isNotEmpty()) {
            $this->selectedExamId = $this->exams->first()->id;
        } else {
            $this->selectedExamId = '';
        }
    }

    public function mount()
    {
        $user = Auth::user();
        $this->sessions = AcademicSession::orderBy('start_date', 'desc')->get();
        $this->selectedSession = AcademicSession::getActiveSessionId();

        $userClassId = $user->getSessionClassId($this->selectedSession);

        // 1. View All / Restricted Permission
        if ($user->can('reports.view') || $user->can('reports.view-all-classes')) {
             // Check if manual restriction exists FIRST
           $restrictedIds = DB::table('user_class_access')
                ->where('user_id', $user->id)
                ->pluck('class_id')
                ->toArray();
           
           if (!empty($restrictedIds)) {
               $this->classes = Classes::whereIn('id', $restrictedIds)
                   ->where('academic_session_id', $this->selectedSession)
                   ->orderBy('numeric_value')
                   ->get();
           } elseif ($user->can('reports.view-all-classes')) {
               $this->classes = Classes::where('academic_session_id', $this->selectedSession)
                   ->orderBy('numeric_value')
                   ->get();
           } else {
               // Fallback if just 'reports.view' but no restricted list (Own class only)
                $this->classes = $userClassId ? Classes::where('id', $userClassId)->get() : collect();
           }
        } 
        // 2. Class Teacher Check: Only own class
        elseif ($userClassId) {
            $this->classes = Classes::where('id', $userClassId)->get();
        } 
        // 3. Fallback: No access
        else {
            $this->classes = collect();
        }

        // Set Default Selection
        if ($this->classes->isNotEmpty()) {
              if ($this->selectedClass) {
                  // Already set (e.g. wire:model persistence or passed in)
              } elseif ($userClassId && $this->classes->where('id', $userClassId)->isNotEmpty()) {
                 $this->selectedClass = $userClassId;
             } else {
                 $this->selectedClass = $this->classes->first()->id;
             }
        }
        
        $this->updatedSelectedClass();
    }

    public function generate()
    {
        if (!$this->classId) {
            session()->flash('error', 'No class assigned to you.');
            return;
        }
        
        $this->validate([
            'selectedExamId' => 'required',
        ]);

        $this->isLoading = true;

        try {
            // Get exam name
            $exam = Exam::find($this->selectedExamId);
            $this->examName = $exam ? $exam->name : 'Examination';
            
            // Fetch Subjects for this class
            $subjects = DB::table('subjects')
                ->where('class_id', $this->classId)
                ->orderBy('name')
                ->get();
            
            $this->columnHeaders = $subjects->pluck('name', 'id')->toArray();
            
            // Get max marks from marks_configs
            $marksConfigs = DB::table('marks_configs')
                ->where('exam_id', $this->selectedExamId)
                ->where('class_id', $this->classId)
                ->get()
                ->keyBy('subject');
            
            foreach ($subjects as $subject) {
                $config = $marksConfigs->get($subject->name);
                $this->subjectMaxMarks[$subject->id] = $config ? (int)$config->total_marks : 100;
                $this->subjectPassingMarks[$subject->id] = $config ? (int)$config->passing_marks : 33;
            }

            // Fetch Students with all needed fields
            $students = DB::table('students')
                ->where('class_id', $this->classId)
                ->orderByRaw('CAST(roll_no AS INTEGER) ASC')
                ->get();

            // Fetch All Marks for this Exam/Class
            $marks = DB::table('exam_marks')
                ->where('exam_id', $this->selectedExamId)
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->whereIn('student_id', $students->pluck('id'))
                ->get();

            // Transform Data
            $processedData = $students->map(function ($student) use ($marks, $subjects) {
                $row = [
                    'id' => $student->id,
                    'roll_no' => $student->roll_no,
                    'admission_no' => $student->admission_no ?? '-',
                    'name' => $student->name,
                    'father_name' => $student->father_name ?? '-',
                    'subjects' => [],
                    'total_obtained' => 0,
                    'max_total' => 0,
                    'failed_subjects' => [],
                    'absent_subjects' => [],
                ];

                foreach ($subjects as $subject) {
                    $markRecord = $marks->where('student_id', $student->id)
                                        ->where('subject_id', $subject->id)
                                        ->first();
                    
                    $maxMarks = $this->subjectMaxMarks[$subject->id] ?? 100;
                    
                    if ($markRecord) {
                        // Check if marked as absent
                        if (!empty($markRecord->is_absent)) {
                            $row['subjects'][$subject->id] = [
                                'score' => null,
                                'max' => $maxMarks,
                                'is_absent' => true,
                            ];
                            $row['max_total'] += $maxMarks;
                            $row['absent_subjects'][] = $subject->name;
                        } else {
                            $obtained = (float)$markRecord->marks_obtained;
                            $row['subjects'][$subject->id] = [
                                'score' => $obtained,
                                'max' => $maxMarks,
                                'is_absent' => false,
                            ];
                            $row['total_obtained'] += $obtained;
                            $row['max_total'] += $maxMarks;
                            
                            // Check if failed using per-subject passing marks percentage
                            $passingPct = $this->subjectPassingMarks[$subject->id] ?? 33;
                            $passingScore = ($maxMarks * $passingPct) / 100;
                            $isFailed = $obtained < $passingScore;
                            
                            $row['subjects'][$subject->id]['is_failed'] = $isFailed;
                            
                            if ($isFailed) {
                                $row['failed_subjects'][] = $subject->name;
                            }
                        }
                    } else {
                        $row['subjects'][$subject->id] = [
                            'score' => null,
                            'max' => $maxMarks,
                        ];
                        $row['max_total'] += $maxMarks;
                        // No mark record = not yet entered, don't count as absent
                    }
                }

                $row['percentage'] = $row['max_total'] > 0 
                    ? round(($row['total_obtained'] / $row['max_total']) * 100, 1) 
                    : 0;

                // Grade & Remarks
                $p = $row['percentage'];
                if ($p >= 90) { $row['grade'] = 'A+'; $row['remarks'] = 'Outstanding'; }
                elseif ($p >= 80) { $row['grade'] = 'A'; $row['remarks'] = 'Excellent'; }
                elseif ($p >= 70) { $row['grade'] = 'B'; $row['remarks'] = 'Very Good'; }
                elseif ($p >= 60) { $row['grade'] = 'C'; $row['remarks'] = 'Good'; }
                elseif ($p >= 50) { $row['grade'] = 'D'; $row['remarks'] = 'Satisfactory'; }
                else { $row['grade'] = 'F'; $row['remarks'] = 'Needs Improvement'; }

                // Summary
                $row['summary'] = (count($row['failed_subjects']) === 0 && count($row['absent_subjects']) === 0) 
                    ? 'Pass' 
                    : 'Fail';

                return $row;
            })->toArray();

            // Calculate Position (rank by total_obtained descending)
            usort($processedData, fn($a, $b) => $b['total_obtained'] <=> $a['total_obtained']);
            foreach ($processedData as $index => &$row) {
                $row['position'] = $index + 1;
            }
            
            // Re-sort by roll_no for display
            usort($processedData, fn($a, $b) => $a['roll_no'] <=> $b['roll_no']);
            
            $this->reportData = $processedData;

            session()->flash('message', 'Report generated successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Error generating report: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.teacher.result-report')
            ->layout('components.layouts.teacher', ['title' => 'Result Report']);
    }
}
