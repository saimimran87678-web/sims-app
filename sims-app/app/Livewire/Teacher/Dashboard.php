<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        // 1. Get Active Session Correctly
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        // 2. Count Allocated Subjects (Total teaching assignments)
        // Manual Allocations
        $manualAllocations = DB::table('subject_allocations')
            ->join('classes', 'subject_allocations.class_id', '=', 'classes.id')
            ->where('subject_allocations.user_id', $user->id)
            ->where('classes.academic_session_id', $activeSessionId)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select('subject_allocations.*')
            ->get();
            
        $allocatedCount = $manualAllocations->count();
        
        // Class Teacher Primary Subject
        // If user is class teacher AND has a class_subject defined
        $userClassId = $user->getSessionClassId($activeSessionId);
        $userClassSubject = $user->getSessionClassSubject($activeSessionId);

        $hasInherentSubject = false;
        if ($userClassId && !empty($userClassSubject)) {
             $ownClass = \App\Models\Classes::withoutGlobalScope('active_session')->find($userClassId);
             if ($ownClass && ($shiftType === 'both' || $ownClass->shift_type === $shiftType)) {
                 $hasInherentSubject = true;
             } else {
                 $userClassId = null; // Mismatched shift, ignore
             }
        }
        
        $totalSubjects = $allocatedCount + ($hasInherentSubject ? 1 : 0);


        // 3. Count Students
        // Get all unique class IDs the teacher interacts with
        $classIds = $manualAllocations->pluck('class_id')->toArray();
        
        if ($userClassId) {
            $classIds[] = $userClassId;
        }
        $classIds = array_unique($classIds);

        $studentsCount = 0;
        if (!empty($classIds)) {
             $studentsCount = DB::table('enrollments')
                ->whereIn('class_id', $classIds)
                ->where('academic_session_id', $activeSessionId)
                ->where('status', 'active')
                ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                    $q->where('shift_type', $shiftType);
                })
                ->count();
        }

        // 4. Fetch Today's Schedule
        // Explicitly set timezone to Karachi/App Timezone
        $now = now(); 
        $day = $now->format('l'); // Monday, Tuesday...

        $periods = DB::table('period_configs')
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('shift_type', $shiftType);
            })
            ->orderBy('period_no')
            ->get();
        
        $todaySchedule = collect();
        if ($activeSessionId) {
            $scheduleType = \App\Services\LicenseStatus::getEffectiveScheduleType();
            $dayToQuery = ($scheduleType === 'single_schedule') ? 'Monday' : $day;

            $baseSchedule = DB::table('timetables')
                ->join('classes', 'timetables.class_id', '=', 'classes.id')
                ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
                ->where('teacher_id', $user->id)
                ->where('classes.academic_session_id', $activeSessionId)
                ->where('day', $dayToQuery)
                ->where('is_substitute', 0)
                ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                    $q->where('classes.shift_type', $shiftType);
                })
                ->select('timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
                ->get();
                
            // Fetch substitute duties for today from dedicated substitutions table
            $assignedSubs = \App\Models\Substitution::where('date', $now->format('Y-m-d'))
                ->where('substitute_teacher_id', $user->id)
                ->with(['classRoom', 'subject', 'absentTeacher'])
                ->get()
                ->keyBy('period_no');

            // Check if user is absent or has a substitute covering their periods today
            $relievedSubs = \App\Models\Substitution::where('date', $now->format('Y-m-d'))
                ->where('absent_teacher_id', $user->id)
                ->with(['classRoom', 'subject', 'substituteTeacher'])
                ->get()
                ->keyBy('period_no');

            $allPeriodNos = $baseSchedule->pluck('period_no')->merge($assignedSubs->keys())->merge($relievedSubs->keys())->unique();
            $combinedSchedule = collect();

            foreach ($allPeriodNos as $pNo) {
                $regular = $baseSchedule->firstWhere('period_no', $pNo);
                $arr = $assignedSubs->get($pNo);
                $rel = $relievedSubs->get($pNo);

                if ($arr && $regular) {
                    // Admin override! Regular period is overridden by arrangement duty
                    $combinedSchedule->push((object) [
                        'id' => 'override_' . $arr->id,
                        'period_no' => $pNo,
                        'class_id' => $arr->class_id,
                        'subject_id' => $arr->subject_id,
                        'teacher_id' => $arr->substitute_teacher_id,
                        'day' => $day,
                        'is_substitute' => 1,
                        'is_override' => true,
                        'is_relieved' => false,
                        'class_name' => $arr->classRoom?->name ?? 'N/A',
                        'subject_name' => ($arr->subject?->name ?? 'Arrangement') . ($arr->absentTeacher ? ' (Covering for ' . $arr->absentTeacher->name . ')' : ''),
                        'overridden_class_name' => $regular->class_name,
                        'overridden_subject_name' => $regular->subject_name,
                        'absent_teacher_name' => $arr->absentTeacher?->name,
                    ]);
                } elseif ($arr) {
                    // Arrangement duty on free period
                    $combinedSchedule->push((object) [
                        'id' => 'sub_' . $arr->id,
                        'period_no' => $pNo,
                        'class_id' => $arr->class_id,
                        'subject_id' => $arr->subject_id,
                        'teacher_id' => $arr->substitute_teacher_id,
                        'day' => $day,
                        'is_substitute' => 1,
                        'is_override' => false,
                        'is_relieved' => false,
                        'class_name' => $arr->classRoom?->name ?? 'N/A',
                        'subject_name' => ($arr->subject?->name ?? 'Arrangement') . ($arr->absentTeacher ? ' (Covering for ' . $arr->absentTeacher->name . ')' : ''),
                        'absent_teacher_name' => $arr->absentTeacher?->name,
                    ]);
                } elseif ($rel && $regular) {
                    // Relieved by another teacher
                    $combinedSchedule->push((object) [
                        'id' => 'rel_' . $rel->id,
                        'period_no' => $pNo,
                        'class_id' => $regular->class_id,
                        'subject_id' => $regular->subject_id,
                        'teacher_id' => $regular->teacher_id,
                        'day' => $day,
                        'is_substitute' => 0,
                        'is_override' => false,
                        'is_relieved' => true,
                        'class_name' => $regular->class_name,
                        'subject_name' => $regular->subject_name,
                        'substitute_teacher_name' => $rel->substituteTeacher?->name ?? 'Substitute',
                    ]);
                } elseif ($regular) {
                    // Normal regular class
                    $combinedSchedule->push((object) array_merge((array) $regular, [
                        'is_override' => false,
                        'is_relieved' => false,
                    ]));
                }
            }

            $todaySchedule = $combinedSchedule->keyBy('period_no');
        }

        $stats = [
            'students' => $studentsCount,
            'subjects' => $totalSubjects,
            'classes_today' => $todaySchedule->count(),
        ];

        return view('livewire.teacher.dashboard', [
            'stats' => $stats,
            'periods' => $periods,
            'todaySchedule' => $todaySchedule,
        ])->layout('components.layouts.teacher', ['title' => 'Dashboard']);
    }
}
