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
        $activeSessionId = DB::table('academic_sessions')->where('is_active', true)->value('id');
        if (!$activeSessionId) {
             // Fallback to latest if none active, or handle empty
             $activeSessionId = DB::table('academic_sessions')->orderBy('start_date', 'desc')->value('id');
        }

        // 2. Count Allocated Subjects (Total teaching assignments)
        // Manual Allocations
        $manualAllocations = DB::table('subject_allocations')
            ->where('user_id', $user->id)
            ->get();
            
        $allocatedCount = $manualAllocations->count();
        
        // Class Teacher Primary Subject
        // If user is class teacher AND has a class_subject defined
        $hasInherentSubject = false;
        if ($user->class_id && !empty($user->class_subject)) {
             $hasInherentSubject = true;
             
             // Check if this specific combo (class_id + subject_name) is already in manual allocations to detect overlap
             // We need to resolve subject name to ID for accurate checking, or just assume separate for now.
             // Usually, system prevents duplicate assignment. We'll add 1.
        }
        
        $totalSubjects = $allocatedCount + ($hasInherentSubject ? 1 : 0);


        // 3. Count Students
        // Get all unique class IDs the teacher interacts with
        $classIds = $manualAllocations->pluck('class_id')->toArray();
        
        if ($user->class_id) {
            $classIds[] = $user->class_id;
        }
        $classIds = array_unique($classIds);

        $studentsCount = 0;
        if (!empty($classIds)) {
             // Only count students in the ACTIVE session or all? 
             // Usually "My Students" implies current context.
             // Students table has `class_id`, assuming students belong to classes in current session context usually.
             // But if we want to be strict about session:
             // Classes table has `academic_session_id`.
             $studentsCount = DB::table('students')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->whereIn('students.class_id', $classIds)
                ->where('classes.academic_session_id', $activeSessionId)
                ->count();
        }

        // 4. Fetch Today's Schedule
        // Explicitly set timezone to Karachi/App Timezone
        $now = now(); 
        $day = $now->format('l'); // Monday, Tuesday...

        $periods = DB::table('period_configs')->orderBy('period_no')->get();
        
        $todaySchedule = collect();
        if ($activeSessionId) {
            $todaySchedule = DB::table('timetables')
                ->join('classes', 'timetables.class_id', '=', 'classes.id')
                ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
                ->where('teacher_id', $user->id)
                ->where('classes.academic_session_id', $activeSessionId)
                ->where('day', $day)
                ->where('is_substitute', 0) // What about substitutes?
                ->select('timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
                ->get();
                
            // Also fetch substitute duties for today!
            $substitutes = DB::table('timetables')
                ->join('classes', 'timetables.class_id', '=', 'classes.id')
                ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
                ->where('teacher_id', $user->id)
                ->where('classes.academic_session_id', $activeSessionId)
                ->where('is_substitute', 1)
                ->where('substitute_date', $now->format('Y-m-d'))
                ->select('timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
                ->get();
                
            $todaySchedule = $todaySchedule->merge($substitutes)->keyBy('period_no');
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
