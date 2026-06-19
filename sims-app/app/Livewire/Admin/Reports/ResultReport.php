<?php

namespace App\Livewire\Admin\Reports;

use Livewire\Component;
use App\Models\Exam;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;

class ResultReport extends Component
{
    public $exams = [];
    public $classes = [];
    
    // Session Management
    public $selectedSessionId;
    public $academicSessions = [];

    protected $queryString = ['selectedSessionId'];
    
    public $selectedExamId = '';
    public $selectedClassId = '';
    public $className = '';
    public $examName = '';
    
    public $reportData = [];
    public $columnHeaders = []; // subject_id => name
    public $subjectMaxMarks = []; // subject_id => max
    public $subjectPassingMarks = []; // subject_id => passing percentage
    public $isLoading = false;

    public function mount()
    {
        $this->academicSessions = \App\Models\AcademicSession::orderBy('start_date', 'desc')->get();
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        // Enforce Data Scope
        if (!auth()->user()->can('reports.view-sessions') && !auth()->user()->hasRole('Super Admin')) {
            $this->selectedSessionId = $activeSessionId;
            $this->academicSessions = $this->academicSessions->where('id', $activeSessionId);
        } else {
            $this->selectedSessionId = $activeSessionId;
        }
        
        $this->loadDropdowns();
    }

    public function updatedSelectedSessionId()
    {
        $this->loadDropdowns();
        $this->reset(['selectedExamId', 'selectedClassId', 'reportData']);
    }

    public function loadDropdowns()
    {
        if ($this->selectedSessionId) {
            $this->exams = Exam::where('academic_session_id', $this->selectedSessionId)
                ->orderBy('created_at', 'desc')
                ->get();
                
            $this->classes = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->selectedSessionId)
                ->orderBy('numeric_value')
                ->get();
        } else {
            $this->exams = [];
            $this->classes = [];
        }
    }

    public function updatedSelectedClassId()
    {
        $class = Classes::find($this->selectedClassId);
        $this->className = $class ? $class->name : '';
    }

    public function updatedSelectedExamId()
    {
        $exam = Exam::find($this->selectedExamId);
        $this->examName = $exam ? $exam->name : '';
    }

    public function generate()
    {
        $this->validate([
            'selectedExamId' => 'required',
            'selectedClassId' => 'required',
        ]);

        $this->isLoading = true;

        try {
            // Get class and exam names
            $class = Classes::find($this->selectedClassId);
            $exam = Exam::find($this->selectedExamId);
            $this->className = $class ? $class->name : 'Unknown Class';
            $this->examName = $exam ? $exam->name : 'Examination';

            // Fetch Subjects
            $subjects = DB::table('subjects')
                ->where('class_id', $this->selectedClassId)
                ->orderBy('name')
                ->get();
            
            $this->columnHeaders = $subjects->pluck('name', 'id')->toArray();

            // Get max marks from marks_configs
            $marksConfigs = DB::table('marks_configs')
                ->where('exam_id', $this->selectedExamId)
                ->where('class_id', $this->selectedClassId)
                ->get()
                ->keyBy('subject');
            
            foreach ($subjects as $subject) {
                $config = $marksConfigs->get($subject->name);
                $this->subjectMaxMarks[$subject->id] = $config ? (int)$config->total_marks : 100;
                $this->subjectPassingMarks[$subject->id] = $config ? (int)$config->passing_marks : 33;
            }

            // Fetch Students
            $students = \App\Models\Student::with('subjects')
                ->where('class_id', $this->selectedClassId)
                ->orderByRaw('CAST(roll_no AS INTEGER) ASC')
                ->get();

            // Fetch Marks
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

                $studentSubjectIds = $student->subjects->pluck('id')->toArray();

                foreach ($subjects as $subject) {
                    $isEnrolled = empty($studentSubjectIds) || in_array($subject->id, $studentSubjectIds);
                    $maxMarks = $this->subjectMaxMarks[$subject->id] ?? 100;

                    if (!$isEnrolled) {
                        $row['subjects'][$subject->id] = [
                            'score' => '-',
                            'max' => '-',
                            'is_absent' => false,
                            'is_failed' => false,
                            'not_enrolled' => true,
                        ];
                        continue;
                    }

                    $markRecord = $marks->where('student_id', $student->id)
                                        ->where('subject_id', $subject->id)
                                        ->first();
                    
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

            // Calculate Position
            usort($processedData, fn($a, $b) => $b['total_obtained'] <=> $a['total_obtained']);
            foreach ($processedData as $index => &$row) {
                $row['position'] = $index + 1;
            }
            
            // Re-sort by roll_no
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
        return view('livewire.admin.reports.result-report');
    }
}
