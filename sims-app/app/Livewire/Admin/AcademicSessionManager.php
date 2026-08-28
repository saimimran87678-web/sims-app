<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\AcademicSession;
use App\Models\Classes;
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
        
        $activeClassesCount = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $activeSessionId)
            ->count();

        $targetClasses = collect();
        if ($this->toSessionId) {
            $targetClasses = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->toSessionId)
                ->orderBy('numeric_value')
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

        // Also clean up related records before deleting
        DB::table('session_user')->where('academic_session_id', $id)->delete();
        Classes::withoutGlobalScope('active_session')->where('academic_session_id', $id)->delete();

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

        // Auto-copy classes and subjects from source session if target session has zero classes
        $targetClassCount = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->toSessionId)
            ->count();
        
        if ($targetClassCount === 0) {
            $sourceClasses = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $this->fromSessionId)
                ->get();
            $classMap = [];
            foreach ($sourceClasses as $sc) {
                $targetShifts = [];
                if ($this->toSessionIsRegular) {
                    if ($sc->shift_type === 'morning' || $sc->shift_type === 'regular') {
                        $targetShifts[] = 'regular';
                    }
                } else {
                    if ($sc->shift_type === 'regular') {
                        $targetShifts = ['morning', 'evening'];
                    } else {
                        $targetShifts = [$sc->shift_type];
                    }
                }

                foreach ($targetShifts as $tShift) {
                    $newClass = Classes::create([
                        'academic_session_id' => $this->toSessionId,
                        'name' => $sc->name,
                        'numeric_value' => $sc->numeric_value,
                        'shift_type' => $tShift,
                    ]);
                    $classMap[$sc->id][$tShift] = $newClass->id;

                    $sourceSubjects = Subject::where('class_id', $sc->id)->get();
                    foreach ($sourceSubjects as $subj) {
                        Subject::create([
                            'class_id' => $newClass->id,
                            'name' => $subj->name,
                        ]);
                    }
                }
            }

            // Map next_class_ids of newly created classes
            foreach ($sourceClasses as $sc) {
                if ($sc->next_class_id) {
                    $targetShifts = $this->toSessionIsRegular ? ['regular'] : ['morning', 'evening'];
                    foreach ($targetShifts as $tShift) {
                        $oldClassId = $sc->id;
                        $oldNextClassId = $sc->next_class_id;

                        if (isset($classMap[$oldClassId][$tShift]) && isset($classMap[$oldNextClassId][$tShift])) {
                            $newClassId = $classMap[$oldClassId][$tShift];
                            $newNextClassId = $classMap[$oldNextClassId][$tShift];

                            Classes::withoutGlobalScope('active_session')->where('id', $newClassId)->update([
                                'next_class_id' => $newNextClassId
                            ]);
                        }
                    }
                }
            }
        }

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
                'enrollments.roll_number'
            )
            ->get();

        if ($enrollments->isEmpty()) {
            session()->flash('error', 'No active student enrollments found in the selected source session.');
            $this->showPromotionPreview = false;
            return;
        }

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
            $subjects = DB::table('subjects')
                ->whereIn('class_id', $classIds)
                ->get()
                ->groupBy('class_id');

            $marksConfigs = DB::table('marks_configs')
                ->where('exam_id', $finalExam->id)
                ->whereIn('class_id', $classIds)
                ->get()
                ->groupBy('class_id');

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
                $nextClassSource = Classes::withoutGlobalScope('active_session')->find($e->next_class_id);
                if ($nextClassSource) {
                    if ($nextClassSource->academic_session_id == $this->toSessionId) {
                        if ($nextClassSource->shift_type === $targetShift) {
                            $defaultTargetClassId = (string) $nextClassSource->id;
                        } else {
                            $targetNextClass = Classes::withoutGlobalScope('active_session')
                                ->where('academic_session_id', $this->toSessionId)
                                ->where('name', $nextClassSource->name)
                                ->where('shift_type', $targetShift)
                                ->first();
                            if ($targetNextClass) {
                                $defaultTargetClassId = (string) $targetNextClass->id;
                            }
                        }
                    } else {
                        $targetNextClass = Classes::withoutGlobalScope('active_session')
                            ->where('academic_session_id', $this->toSessionId)
                            ->where('name', $nextClassSource->name)
                            ->where('shift_type', $targetShift)
                            ->first();
                        if ($targetNextClass) {
                            $defaultTargetClassId = (string) $targetNextClass->id;
                        }
                    }
                }
            } else {
                $status = 'passed_out';
            }

            $hasPassedAll = true;

            if ($finalExam) {
                $classSubjects = $subjects->get($e->class_id) ?? collect();
                $studentMarks = $examMarks->get($e->student_id) ?? collect();
                $classConfigs = $marksConfigs->get($e->class_id) ?? collect();
                $configsBySubjectName = $classConfigs->keyBy('subject');

                if ($classSubjects->isEmpty()) {
                    $hasPassedAll = true;
                } else {
                    foreach ($classSubjects as $subject) {
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
                if ($item['status'] === 'passed_out') {
                    $oldStatus = 'passed_out';
                    $globalStatus = 'inactive';
                } elseif ($item['status'] === 'left_school') {
                    $oldStatus = 'transferred';
                    $globalStatus = 'inactive';
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
                        $oldClass = Classes::withoutGlobalScope('active_session')->find($oldEnroll->class_id);
                        if ($oldClass) {
                            $targetClass = Classes::withoutGlobalScope('active_session')
                                ->where('academic_session_id', $this->toSessionId)
                                ->where('name', $oldClass->name)
                                ->where('shift_type', $item['target_shift'])
                                ->first();
                            if ($targetClass) {
                                $targetClassId = $targetClass->id;
                            }
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
            
            if ($this->massStatus === 'promote') {
                $enroll = DB::table('enrollments')->where('id', $item['enrollment_id'])->first();
                if ($enroll) {
                    $class = Classes::withoutGlobalScope('active_session')->find($enroll->class_id);
                    if ($class && $class->next_class_id) {
                        $nextClassSource = Classes::withoutGlobalScope('active_session')->find($class->next_class_id);
                        if ($nextClassSource) {
                            if ($nextClassSource->academic_session_id == $this->toSessionId) {
                                if ($nextClassSource->shift_type === $item['target_shift']) {
                                    $item['target_class_id'] = (string) $nextClassSource->id;
                                } else {
                                    $targetNextClass = Classes::withoutGlobalScope('active_session')
                                        ->where('academic_session_id', $this->toSessionId)
                                        ->where('name', $nextClassSource->name)
                                        ->where('shift_type', $item['target_shift'])
                                        ->first();
                                    if ($targetNextClass) {
                                        $item['target_class_id'] = (string) $targetNextClass->id;
                                    }
                                }
                            } else {
                                $targetNextClass = Classes::withoutGlobalScope('active_session')
                                    ->where('academic_session_id', $this->toSessionId)
                                    ->where('name', $nextClassSource->name)
                                    ->where('shift_type', $item['target_shift'])
                                    ->first();
                                if ($targetNextClass) {
                                    $item['target_class_id'] = (string) $targetNextClass->id;
                                }
                            }
                        }
                    }
                }
            } elseif ($this->massStatus === 'repeater') {
                $oldClass = Classes::withoutGlobalScope('active_session')->find($item['current_class_id']);
                if ($oldClass) {
                    $targetClass = Classes::withoutGlobalScope('active_session')
                        ->where('academic_session_id', $this->toSessionId)
                        ->where('name', $oldClass->name)
                        ->where('shift_type', $item['target_shift'])
                        ->first();
                    if ($targetClass) {
                        $item['target_class_id'] = (string) $targetClass->id;
                    } else {
                        $firstSameGradeClass = Classes::withoutGlobalScope('active_session')
                            ->where('academic_session_id', $this->toSessionId)
                            ->where('numeric_value', $item['current_class_numeric_value'])
                            ->where('shift_type', $item['target_shift'])
                            ->first();
                        if ($firstSameGradeClass) {
                            $item['target_class_id'] = (string) $firstSameGradeClass->id;
                        } else {
                            $item['target_class_id'] = '';
                        }
                    }
                }
            } else {
                $item['target_class_id'] = '';
            }
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
                if ($value === 'repeater') {
                    // Auto-resolve same-name class in target session as default target_class_id
                    $oldClass = Classes::withoutGlobalScope('active_session')->find($item['current_class_id']);
                    if ($oldClass) {
                        $targetClass = Classes::withoutGlobalScope('active_session')
                            ->where('academic_session_id', $this->toSessionId)
                            ->where('name', $oldClass->name)
                            ->where('shift_type', $item['target_shift'])
                            ->first();
                        if ($targetClass) {
                            $this->promotionPreview[$index]['target_class_id'] = (string) $targetClass->id;
                        } else {
                            // Fallback to first target class with same numeric value
                            $firstSameGradeClass = Classes::withoutGlobalScope('active_session')
                                ->where('academic_session_id', $this->toSessionId)
                                ->where('numeric_value', $item['current_class_numeric_value'])
                                ->where('shift_type', $item['target_shift'])
                                ->first();
                            if ($firstSameGradeClass) {
                                $this->promotionPreview[$index]['target_class_id'] = (string) $firstSameGradeClass->id;
                            } else {
                                $this->promotionPreview[$index]['target_class_id'] = '';
                            }
                        }
                    }
                } elseif ($value === 'promote') {
                    // Auto-resolve promoted next class
                    $oldClass = Classes::withoutGlobalScope('active_session')->find($item['current_class_id']);
                    if ($oldClass && $oldClass->next_class_id) {
                        $nextClassSource = Classes::withoutGlobalScope('active_session')->find($oldClass->next_class_id);
                        if ($nextClassSource) {
                            if ($nextClassSource->academic_session_id == $this->toSessionId) {
                                if ($nextClassSource->shift_type === $item['target_shift']) {
                                    $this->promotionPreview[$index]['target_class_id'] = (string) $nextClassSource->id;
                                } else {
                                    $targetNextClass = Classes::withoutGlobalScope('active_session')
                                        ->where('academic_session_id', $this->toSessionId)
                                        ->where('name', $nextClassSource->name)
                                        ->where('shift_type', $item['target_shift'])
                                        ->first();
                                    if ($targetNextClass) {
                                        $this->promotionPreview[$index]['target_class_id'] = (string) $targetNextClass->id;
                                    }
                                }
                            } else {
                                $targetNextClass = Classes::withoutGlobalScope('active_session')
                                    ->where('academic_session_id', $this->toSessionId)
                                    ->where('name', $nextClassSource->name)
                                    ->where('shift_type', $item['target_shift'])
                                    ->first();
                                if ($targetNextClass) {
                                    $this->promotionPreview[$index]['target_class_id'] = (string) $targetNextClass->id;
                                }
                            }
                        }
                    } else {
                        $this->promotionPreview[$index]['target_class_id'] = '';
                    }
                } else {
                    $this->promotionPreview[$index]['target_class_id'] = '';
                }
            }
        }
    }

    public function runAutoUpdate()
    {
        Artisan::call('app:update-academic-session');
        session()->flash('message', 'Auto-update ran successfully. ' . trim(Artisan::output()));
    }
}
