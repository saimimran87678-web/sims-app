<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public function render()
    {
        // Calculate Stats
        $stats = [
            'users' => User::count(),
            'classes' => DB::table('classes')->count(),
            'students' => DB::table('students')->count(),
            'attendance' => 0, // Default
        ];

        // Calculate simplified attendance percentage if data exists
        $totalAttendanceRecords = DB::table('attendances')->count();
        if ($totalAttendanceRecords > 0) {
            $presentCount = DB::table('attendances')->where('status', 'P')->count();
            $stats['attendance'] = round(($presentCount / $totalAttendanceRecords) * 100, 1);
        }

        $classes = \App\Models\Classes::orderBy('numeric_value')->orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();

        return view('livewire.admin.dashboard', [
            'stats' => $stats,
            'classes' => $classes,
            'teachers' => $teachers
        ])->layout('components.layouts.admin', ['title' => 'Dashboard']);
    }
}
