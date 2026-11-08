<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class AcademicSessionManager extends Component
{
    // Session form fields
    public $name, $start_date, $end_date, $is_active = false, $shift_type;
    public $sessionId;
    public $isModalOpen = false;

    // Class management panel (inline per session)
    public $managingSessionId = null;   // which session's class panel is open
    public $managingSessionName = '';
    public $selectedClassIds = [];      // class IDs admin has checked for this session

    // Promotion workflow properties
    public $fromSessionId;
    public $toSessionId;
    public $promotionPreview = [];
    public $autoPromoteList = [];
    public $showPromotionPreview = false;
    public $toSessionIsRegular = false;
    public $searchQuery = '';
    public $massStatus = 'promote';
    public $massShift = '';

    public $isTeacherContext = false;

    public function mount()
    {
        $this->authorize('sessions.manage');
        $this->isTeacherContext = request()->is('teacher/*');
    }

    public function render()
    {
        $sessions = AcademicSession::orderBy('start_date', 'desc')->get();
        $activeSessionId = AcademicSession::getActiveSessionId();
        
        $activeClassesCount = Classes::where('is_active', true)->count();

        $targetClasses = collect();
        if ($this->toSessionId) {
            $targetClasses = Classes::where('is_active', true)
                ->when($this->toSessionIsRegular, fn($q) => $q->where('shift_type', 'regular'))
                ->orderBy('numeric_value')
                ->orderBy('name')
                ->get();
        }

        $layout = $this->isTeacherContext
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        return view('livewire.admin.academic-session-manager', [
            'sessions'           => $sessions,
            'activeClassesCount' => $activeClassesCount,
            'targetClasses'      => $targetClasses,
        ])->layout($layout, ['title' => 'Academic Sessions']);
    }

    // -----------------------------------------------------------------------
    // SESSION CRUD
    // -----------------------------------------------------------------------

    public function create()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate([
            'name'       => 'required',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'shift_type' => 'required|in:Regular,Dual',
        ]);

        $oldSession = $this->sessionId ? AcademicSession::find($this->sessionId) : null;
        $oldShiftType = $oldSession ? $oldSession->shift_type : null;

        $session = AcademicSession::updateOrCreate(['id' => $this->sessionId], [
            'name'       => $this->name,
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
            'is_active'  => $this->is_active,
            'shift_type' => $this->shift_type,
        ]);

        if ($oldShiftType && $oldShiftType !== $this->shift_type) {
            if ($this->shift_type === 'Dual' && $oldShiftType === 'Regular') {
                // Enrollments: regular -> morning
                DB::table('enrollments')
                    ->where('academic_session_id', $session->id)
                    ->where('shift_type', 'regular')
                    ->update(['shift_type' => 'morning']);

                // Classes: regular -> morning
                DB::table('classes')
                    ->where('academic_session_id', $session->id)
                    ->where('shift_type', 'regular')
                    ->update(['shift_type' => 'morning']);
            } elseif ($this->shift_type === 'Regular' && $oldShiftType === 'Dual') {
                // Enrollments: morning -> regular
                DB::table('enrollments')
                    ->where('academic_session_id', $session->id)
                    ->where('shift_type', 'morning')
                    ->update(['shift_type' => 'regular']);

                // Classes: morning -> regular
                DB::table('classes')
                    ->where('academic_session_id', $session->id)
                    ->where('shift_type', 'morning')
                    ->update(['shift_type' => 'regular']);
            }
        }

        // Auto-attach all users to a newly created session
        if (!$this->sessionId) {
            $users     = \App\Models\User::pluck('id');
            $pivotData = [];
            foreach ($users as $userId) {
                $pivotData[] = [
                    'user_id'             => $userId,
                    'academic_session_id' => $session->id,
                    'is_active'           => true,
                    'is_primary'          => true,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }
            DB::table('session_user')->insert($pivotData);
        }

        session()->flash('message', $this->sessionId ? 'Session updated successfully.' : 'Session created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $session          = AcademicSession::findOrFail($id);
        $this->sessionId  = $id;
        $this->name       = $session->name;
        $this->start_date = $session->start_date;
        $this->end_date   = $session->end_date;
        $this->is_active  = $session->is_active;
        $this->shift_type = $session->shift_type ?: 'Regular';
        $this->isModalOpen = true;
    }

    public function delete($id)
    {
        if ($this->isTeacherContext) {
            abort(403, 'Unauthorized action.');
        }

        // Safety check — don't delete if it has enrollments or exams
        $hasData = DB::table('enrollments')->where('academic_session_id', $id)->exists()
                || DB::table('exams')->where('academic_session_id', $id)->exists()
                || DB::table('fee_records')->where('academic_session_id', $id)->exists();

        if ($hasData) {
            session()->flash('error', 'Cannot delete this session — it has student enrollments, exams, or fee records attached to it.');
            return;
        }

        // Clean up session-specific assignments before deleting
        DB::table('session_user')->where('academic_session_id', $id)->delete();

        AcademicSession::find($id)->delete();
        session()->flash('message', 'Session deleted successfully.');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetInputFields()
    {
        $this->name       = '';
        $this->start_date = '';
        $this->end_date   = '';
        $this->is_active  = false;
        $this->sessionId  = null;
        $this->shift_type = \App\Models\Setting::getGlobal('default_session_shift_mode', 'Regular');
    }



    // -----------------------------------------------------------------------
    // STUDENT PROMOTION WORKFLOW
    // -----------------------------------------------------------------------

    public function previewPromotion()
    {
        $this->authorize('sessions.manage');
        $this->validate([
            'fromSessionId' => 'required|different:toSessionId',
            'toSessionId' => 'required',
        ]);

        $targetSession = AcademicSession::find($this->toSessionId);
        $this->toSessionIsRegular = ($targetSession && $targetSession->shift_type === 'Regular');

        // Get active enrollments from the source session
        $enrollments = DB::table('enrollments')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('classes', 'classes.id', '=', 'enrollments.class_id')
            ->where('enrollments.academic_session_id', $this->fromSessionId)
            ->where('enrollments.status', 'active')
            ->select(
                'enrollments.id as enrollment_id',
                'enrollments.student_id',
                'students.name as student_name',
                'classes.id as class_id',
                'classes.name as class_name',
                'classes.next_class_id',
                'classes.numeric_value',
                'enrollments.shift_type',
                'enrollments.roll_number',
                'enrollments.program_id'
            )
            ->get();

        if ($enrollments->isEmpty()) {
            session()->flash('error', 'No active student enrollments found in the selected source session.');
            $this->showPromotionPreview = false;
            return;
        }

        // Program to subjects mapping for elective filtering
        $programSubjectsMap = DB::table('program_subjects')
            ->get()
            ->groupBy('program_id')
            ->map(fn($group) => $group->pluck('subject_id')->toArray());

        // Fetch Final-Term exam and associated records to identify passed/failed students
        $finalExam = DB::table('exams')
            ->where('academic_session_id', $this->fromSessionId)
            ->where('type', 'Final-Term')
            ->first();

        $subjects = collect();
        $marksConfigs = collect();
        $examMarks = collect();

        if ($finalExam) {
            $classIds = $enrollments->pluck('class_id')->unique()->toArray();
            $marksConfigs = DB::table('marks_configs')
                ->where('exam_id', $finalExam->id)
                ->whereIn('class_id', $classIds)
                ->get()
                ->groupBy('class_id');

            $subjects = collect();
            foreach ($classIds as $cId) {
                $configuredSubjectIds = $marksConfigs->get($cId)?->pluck('subject_id')->filter()->toArray() ?? [];
                $configuredSubjectNames = $marksConfigs->get($cId)?->pluck('subject')->toArray() ?? [];

                if (!empty($configuredSubjectIds) || !empty($configuredSubjectNames)) {
                    $cSubjects = Subject::where(function ($q) use ($configuredSubjectIds, $configuredSubjectNames) {
                        if (!empty($configuredSubjectIds)) {
                            $q->whereIn('id', $configuredSubjectIds);
                        }
                        if (!empty($configuredSubjectNames)) {
                            $q->orWhereIn('name', $configuredSubjectNames);
                        }
                    })->get();
                } else {
                    $cSubjects = Subject::forClass($cId)->get();
                }
                $subjects->put($cId, $cSubjects);
            }

            $studentIds = $enrollments->pluck('student_id')->unique()->toArray();
            $examMarks = DB::table('exam_marks')
                ->where('exam_id', $finalExam->id)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->groupBy('student_id');
        }

        $this->promotionPreview = [];
        $this->autoPromoteList = [];

        foreach ($enrollments as $e) {
            $defaultTargetClassId = '';
            $status = 'promote';

            $targetShift = $this->toSessionIsRegular
                ? 'regular'
                : (in_array($e->shift_type, ['morning', 'evening']) ? $e->shift_type : 'morning');

            if ($e->next_class_id) {
                $defaultTargetClassId = $this->resolveTargetClassId($e->class_id, 'promote', $targetShift);
            } else {
                $status = 'graduated';
            }

            $hasPassedAll = true;

            if ($finalExam) {
                $classSubjects = $subjects->get($e->class_id) ?? collect();
                $studentMarks = $examMarks->get($e->student_id) ?? collect();
                $classConfigs = $marksConfigs->get($e->class_id) ?? collect();
                $configsBySubjectName = $classConfigs->keyBy('subject');
                $allowedSubjectIds = $e->program_id && isset($programSubjectsMap[$e->program_id])
                    ? $programSubjectsMap[$e->program_id]
                    : null;

                if ($classSubjects->isEmpty()) {
                    $hasPassedAll = true;
                } else {
                    foreach ($classSubjects as $subject) {
                        if ($allowedSubjectIds !== null && !in_array($subject->id, $allowedSubjectIds)) {
                            continue; // Skip elective outside this student's curriculum track
                        }
                        $config = $configsBySubjectName->get($subject->name);
                        $maxMarks = $config ? (int)$config->total_marks : 100;
                        $passingPct = $config ? (int)$config->passing_marks : 33;

                        $markRecord = $studentMarks->where('subject_id', $subject->id)->first();
                        if (!$markRecord) {
                            $hasPassedAll = false;
                            break;
                        }

                        if (!empty($markRecord->is_absent)) {
                            $hasPassedAll = false;
                            break;
                        }

                        $obtained = (float)$markRecord->marks_obtained;
                        $passingScore = ($maxMarks * $passingPct) / 100;
                        if ($obtained < $passingScore) {
                            $hasPassedAll = false;
                            break;
                        }
                    }
                }
            } else {
                $hasPassedAll = false;
            }

            $item = [
                'enrollment_id' => $e->enrollment_id,
                'student_id' => $e->student_id,
                'student_name' => $e->student_name,
                'current_class_id' => $e->class_id,
                'current_class_name' => $e->class_name,
                'current_class_numeric_value' => $e->numeric_value,
                'current_shift' => $e->shift_type,
                'target_class_id' => $defaultTargetClassId,
                'target_shift' => $targetShift,
                'roll_number' => $e->roll_number,
                'status' => $status,
            ];

            if ($hasPassedAll) {
                $this->autoPromoteList[] = $item;
            } else {
                $this->promotionPreview[] = $item;
            }
        }

        $this->showPromotionPreview = true;
    }

    public function cancelPromotion()
    {
        $this->showPromotionPreview = false;
        $this->promotionPreview = [];
        $this->autoPromoteList = [];
    }

    public function savePromotion()
    {
        $this->authorize('sessions.manage');

        if (empty($this->promotionPreview) && empty($this->autoPromoteList)) {
            return;
        }

        DB::transaction(function () {
            $allPromotions = array_merge($this->autoPromoteList, $this->promotionPreview);

            foreach ($allPromotions as $item) {
                // Determine old enrollment status based on action selected
                $oldStatus = 'promoted';
                $globalStatus = 'active';
                if ($item['status'] === 'passed_out' || $item['status'] === 'graduated') {
                    $oldStatus = 'graduated';
                    $globalStatus = 'graduated';
                } elseif ($item['status'] === 'left_school' || $item['status'] === 'slc_issued' || $item['status'] === 'withdrawn') {
                    $oldStatus = 'slc_issued';
                    $globalStatus = 'withdrawn';
                } elseif ($item['status'] === 'repeater') {
                    $oldStatus = 'held_back';
                }

                // Update old enrollment
                DB::table('enrollments')
                    ->where('id', $item['enrollment_id'])
                    ->update([
                        'status' => $oldStatus,
                        'updated_at' => now(),
                    ]);

                // Update global student status
                DB::table('students')
                    ->where('id', $item['student_id'])
                    ->update([
                        'status' => $globalStatus,
                        'updated_at' => now(),
                    ]);

                // Create new enrollment in target session if status is promote or repeater
                if ($item['status'] === 'promote' || $item['status'] === 'repeater') {
                    $targetClassId = $item['target_class_id'];
                    if (!$targetClassId && $item['status'] === 'repeater') {
                        $oldEnroll = DB::table('enrollments')->where('id', $item['enrollment_id'])->first();
                        if ($oldEnroll) {
                            $targetClassId = $this->resolveTargetClassId($oldEnroll->class_id, 'repeater', $item['target_shift']);
                        }
                    }

                    if ($targetClassId) {
                        $exists = DB::table('enrollments')
                            ->where('student_id', $item['student_id'])
                            ->where('academic_session_id', $this->toSessionId)
                            ->where('shift_type', $item['target_shift'])
                            ->exists();

                        if (!$exists) {
                            DB::table('enrollments')->insert([
                                'student_id' => $item['student_id'],
                                'class_id' => $targetClassId,
                                'academic_session_id' => $this->toSessionId,
                                'shift_type' => $item['target_shift'],
                                'roll_number' => $item['roll_number'],
                                'status' => 'active',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });

        session()->flash('message', 'Student promotion processed successfully!');
        $this->showPromotionPreview = false;
        $this->promotionPreview = [];
        $this->autoPromoteList = [];
        $this->fromSessionId = null;
        $this->toSessionId = null;
    }

    public function applyMassStatus()
    {
        foreach ($this->promotionPreview as &$item) {
            $item['status'] = $this->massStatus;
            $item['target_class_id'] = $this->resolveTargetClassId(
                $item['current_class_id'],
                $this->massStatus,
                $item['target_shift']
            );
        }
    }

    public function applyMassShift()
    {
        if (empty($this->massShift)) return;
        foreach ($this->promotionPreview as &$item) {
            $item['target_shift'] = $this->massShift;
        }
    }

    public function updated($name, $value)
    {
        if (str_starts_with($name, 'promotionPreview.')) {
            $parts = explode('.', $name);
            if (count($parts) === 3 && $parts[2] === 'status') {
                $index = (int)$parts[1];
                if (!isset($this->promotionPreview[$index])) {
                    return;
                }
                $item = $this->promotionPreview[$index];
                $this->promotionPreview[$index]['target_class_id'] = $this->resolveTargetClassId(
                    $item['current_class_id'],
                    $value,
                    $item['target_shift']
                );
            }
        }
    }

    private function resolveTargetClassId($classId, $status, $targetShift): string
    {
        $class = Classes::find($classId);
        if (!$class) {
            return '';
        }

        if ($status === 'promote') {
            if (!$class->next_class_id) {
                return '';
            }
            $targetSource = Classes::find($class->next_class_id);
            if (!$targetSource) {
                return '';
            }
        } elseif ($status === 'repeater') {
            $targetSource = $class;
        } else {
            return '';
        }

        // 1. If target session has a specific class (for test/legacy compatibility)
        if ($this->toSessionId) {
            $targetSessionClass = Classes::where('name', $targetSource->name)
                ->where('academic_session_id', $this->toSessionId)
                ->where('shift_type', $targetShift)
                ->first();

            if ($targetSessionClass) {
                return (string) $targetSessionClass->id;
            }
        }

        // 2. Direct match on shift
        if ($targetSource->shift_type === $targetShift) {
            return (string) $targetSource->id;
        }

        // 3. Match by name and target shift
        $targetClass = Classes::where('name', $targetSource->name)
            ->where('shift_type', $targetShift)
            ->first();
        if ($targetClass) {
            return (string) $targetClass->id;
        }

        // 4. Fallback to same grade level in target shift
        $sameGrade = Classes::where('numeric_value', $targetSource->numeric_value)
            ->where('shift_type', $targetShift)
            ->first();

        return (string) ($sameGrade?->id ?? '');
    }

    public function runAutoUpdate()
    {
        Artisan::call('app:update-academic-session');
        session()->flash('message', 'Auto-update ran successfully. ' . trim(Artisan::output()));
    }
}
