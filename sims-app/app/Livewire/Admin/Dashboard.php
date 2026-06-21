<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public function render()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();

        // Calculate Stats
        $classesCount = $activeSessionId 
            ? DB::table('classes')->where('academic_session_id', $activeSessionId)->count()
            : 0;

        $studentsCount = $activeSessionId
            ? DB::table('students')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->where('classes.academic_session_id', $activeSessionId)
                ->where('students.status', 'active')
                ->count()
            : 0;

        $stats = [
            'users' => User::count(),
            'classes' => $classesCount,
            'students' => $studentsCount,
            'attendance' => 0, // Default
        ];

        // Calculate simplified attendance percentage if data exists
        if ($activeSessionId) {
            $totalAttendanceRecords = DB::table('attendances')
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->where('classes.academic_session_id', $activeSessionId)
                ->count();

            if ($totalAttendanceRecords > 0) {
                $presentCount = DB::table('attendances')
                    ->join('students', 'attendances.student_id', '=', 'students.id')
                    ->join('classes', 'students.class_id', '=', 'classes.id')
                    ->where('classes.academic_session_id', $activeSessionId)
                    ->where('attendances.status', 'P')
                    ->count();
                $stats['attendance'] = round(($presentCount / $totalAttendanceRecords) * 100, 1);
            }
        }

        return view('livewire.admin.dashboard', [
            'stats' => $stats
        ])->layout('components.layouts.admin', ['title' => 'Dashboard']);
    }
}
