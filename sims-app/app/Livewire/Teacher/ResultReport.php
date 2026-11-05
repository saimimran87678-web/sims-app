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
    public $filterStatus = 'active';
    
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

        // Determine current shift type
        $sessionObj = AcademicSession::find($this->selectedSession);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        // 1. View All / Restricted Permission
        if ($user->can('reports.view') || $user->can('reports.view-all-classes')) {
             // Check if manual restriction exists FIRST
           $restrictedIds = DB::table('user_class_access')
                ->where('user_id', $user->id)
                ->pluck('class_id')
                ->toArray();
           
           if (!empty($restrictedIds)) {
               $this->classes = Classes::whereIn('classes.id', $restrictedIds)
                   ->where('classes.academic_session_id', $this->selectedSession)
                   ->where('classes.shift_type', $shiftType)
                   ->orderBy('classes.numeric_value')
                   ->get();
           } elseif ($user->can('reports.view-all-classes')) {
               $this->classes = Classes::where('classes.academic_session_id', $this->selectedSession)
                   ->where('classes.shift_type', $shiftType)
                   ->orderBy('classes.numeric_value')
                   ->get();
           } else {
               // Fallback if just 'reports.view' but no restricted list (Own class only)
                 $this->classes = $userClassId ? Classes::where('id', $userClassId)->where('shift_type', $shiftType)->get() : collect();
           }
        } 
        // 2. Class Teacher Check: Only own class
        elseif ($userClassId) {
            $this->classes = Classes::where('id', $userClassId)->where('shift_type', $shiftType)->get();
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
            
            // Fetch Subjects for this exam and class
            $configuredSubjectIds = DB::table('marks_configs')
                ->where('exam_id', $this->selectedExamId)
                ->where('class_id', $this->classId)
                ->pluck('subject_id')
                ->filter()
                ->toArray();

            $configuredSubjectNames = DB::table('marks_configs')
                ->where('exam_id', $this->selectedExamId)
                ->where('class_id', $this->classId)
                ->pluck('subject')
                ->toArray();

            $subjectsQuery = DB::table('subjects');

            if (!empty($configuredSubjectIds) || !empty($configuredSubjectNames)) {
                $subjectsQuery->where(function ($q) use ($configuredSubjectIds, $configuredSubjectNames) {
                    if (!empty($configuredSubjectIds)) {
                        $q->whereIn('id', $configuredSubjectIds);
                    }
                    if (!empty($configuredSubjectNames)) {
                        $q->orWhereIn('name', $configuredSubjectNames);
                    }
                });
            } else {
                $subjectsQuery->where(function ($q) {
                    $q->where('class_id', $this->classId)
                      ->orWhereExists(function ($sub) {
                          $sub->select(DB::raw(1))
                              ->from('class_programs')
                              ->join('program_subjects', 'class_programs.program_id', '=', 'program_subjects.program_id')
                              ->where('class_programs.class_id', $this->classId)
                              ->whereColumn('program_subjects.subject_id', 'subjects.id');
                      });
                });
            }

            $subjects = $subjectsQuery->orderBy('name')->get();
            
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

            $activeSessionId = $this->selectedSession;
            $shiftType = session('selected_shift_type', 'morning');
            if ($shiftType === 'both') {
                $shiftType = 'morning';
            }

            // Fetch Students with all needed fields
            $studentsQuery = \App\Models\Student::with(['subjects', 'enrollments.program.subjects'])
                ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
                ->where('enrollments.class_id', $this->classId)
                ->where('enrollments.academic_session_id', $activeSessionId)
                ->where('enrollments.shift_type', $shiftType)
                ->select('students.*', 'enrollments.roll_number as roll_no', 'enrollments.status', 'enrollments.program_id');

            if ($this->filterStatus === 'active') {
                $studentsQuery->where('enrollments.status', 'active');
            } elseif ($this->filterStatus === 'withdrawn') {
                $studentsQuery->whereIn('enrollments.status', ['withdrawn', 'slc_issued']);
            } elseif ($this->filterStatus === 'graduated') {
                $studentsQuery->where('enrollments.status', 'graduated');
            } elseif ($this->filterStatus === 'struck_off') {
                $studentsQuery->where('enrollments.status', 'struck_off');
            } elseif ($this->filterStatus === 'inactive') {
                $studentsQuery->where('enrollments.status', 'inactive');
            } elseif ($this->filterStatus === 'archived') {
                $studentsQuery->onlyTrashed();
            } else {
                $studentsQuery->withTrashed();
            }

            $students = $studentsQuery->orderByRaw('CAST(enrollments.roll_number AS INTEGER) ASC')->get();

            // Fetch All Marks for this Exam/Class
            $marks = DB::table('exam_marks')
                ->where('exam_id', $this->selectedExamId)
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->whereIn('student_id', $students->pluck('id'))
                ->get();

            // Transform Data
            $processedData = $students->map(function ($student) use ($marks, $subjects, $activeSessionId) {
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

                $activeEnrollment = $student->enrollments->firstWhere('academic_session_id', $activeSessionId);
                $programSubjectIds = $activeEnrollment?->program?->subjects->pluck('id')->toArray() ?? [];
                $legacySubjectIds = $student->subjects->pluck('id')->toArray();
                $enrolledSubjectIds = !empty($programSubjectIds) ? $programSubjectIds : $legacySubjectIds;

                foreach ($subjects as $subject) {
                    $maxMarks = $this->subjectMaxMarks[$subject->id] ?? 100;

                    $markRecord = $marks->where('student_id', $student->id)
                                        ->where('subject_id', $subject->id)
                                        ->first();

                    // isEnrolled: true if student has no subject restrictions, or is specifically enrolled in program,
                    // OR if an admin has explicitly assigned marks for this subject.
                    $isEnrolled = empty($enrolledSubjectIds)
                        || in_array($subject->id, $enrolledSubjectIds)
                        || !is_null($markRecord);

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

                // Grade & Remarks from single source of truth
                $scale = app(\App\Services\GradeCalculationService::class)->getScaleForPercentage(
                    (float) $row['percentage'],
                    $this->selectedSessionId
                );
                $row['grade'] = $scale?->grade ?? 'F';
                $row['remarks'] = $scale?->remarks ?? 'Needs Improvement';

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
