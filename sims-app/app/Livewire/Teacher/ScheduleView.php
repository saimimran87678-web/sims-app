<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\Subject;

class ScheduleView extends Component
{
    public $selectedDay = 'Monday';
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    
    public $periods = [];
    public $classes = [];
    public $timetables = [];

    public function mount()
    {
        // Set current day as default
        $dayOfWeek = now()->dayOfWeek;
        $dayNames = [0 => 'Monday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Monday'];
        $this->selectedDay = $dayNames[$dayOfWeek] ?? 'Monday';
        
        $this->periods = PeriodConfig::orderBy('period_no')->get();
        $this->classes = Classes::orderBy('numeric_value')->get();
        $this->loadTimetables();
    }

    public function loadTimetables()
    {
        $teacherId = Auth::id();
        
        $activeSessionId = DB::table('academic_sessions')->where('is_active', true)->value('id');

        $this->timetables = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $activeSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $this->selectedDay)
            ->where('timetables.is_substitute', false)
            ->select('timetables.*')
            ->get();
    }

    public function updatedSelectedDay()
    {
        $this->loadTimetables();
    }

    public function getClassScheduleByPeriod($periodNo)
    {
        return $this->timetables->where('period_no', $periodNo)->first();
    }

    public function render()
    {
        return view('livewire.teacher.schedule-view')->layout('components.layouts.teacher', ['title' => 'My Schedule']);
    }
}
