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
            $todaySchedule = DB::table('timetables')
                ->join('classes', 'timetables.class_id', '=', 'classes.id')
                ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
                ->where('teacher_id', $user->id)
                ->where('classes.academic_session_id', $activeSessionId)
                ->where('day', $day)
                ->where('is_substitute', 0) // What about substitutes?
                ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                    $q->where('classes.shift_type', $shiftType);
                })
                ->select('timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
                ->get();
                
            // Fetch substitute duties for today from dedicated substitutions table
            $substitutions = \App\Models\Substitution::where('date', $now->format('Y-m-d'))
                ->where('substitute_teacher_id', $user->id)
                ->with(['classRoom', 'subject', 'absentTeacher'])
                ->get()
                ->map(function ($sub) {
                    return (object) [
                        'id' => 'sub_' . $sub->id,
                        'period_no' => $sub->period_no,
                        'class_id' => $sub->class_id,
                        'subject_id' => $sub->subject_id,
                        'teacher_id' => $sub->substitute_teacher_id,
                        'day' => $sub->date ? $sub->date->format('l') : now()->format('l'),
                        'is_substitute' => 1,
                        'class_name' => $sub->classRoom?->name ?? 'N/A',
                        'subject_name' => ($sub->subject?->name ?? 'Arrangement') . ($sub->absentTeacher ? ' (Sub for ' . $sub->absentTeacher->name . ')' : ''),
                    ];
                });

            // Check if user is absent or has a substitute covering their periods today
            $relievedPeriods = \App\Models\Substitution::where('date', $now->format('Y-m-d'))
                ->where('absent_teacher_id', $user->id)
                ->pluck('period_no')
                ->toArray();

            $todaySchedule = $todaySchedule->reject(function ($item) use ($relievedPeriods) {
                return in_array($item->period_no, $relievedPeriods);
            });

            $todaySchedule = $todaySchedule->merge($substitutions)->keyBy('period_no');
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
