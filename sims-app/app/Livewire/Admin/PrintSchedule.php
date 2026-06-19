<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\Subject;

class PrintSchedule extends Component
{
    #[Url]
    public $day = 'Monday';
    
    #[Url]
    public $viewType = 'class'; // class, teacher, summary

    #[Url]
    public $cardsPerPage = 4;

    public $periods = [];
    public $classes = [];
    public $teachers = [];
    public $timetables = [];

    public function mount()
    {
        $this->periods = PeriodConfig::orderBy('period_no')->get();
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $this->classes = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $activeSessionId)
            ->orderBy('numeric_value')
            ->get();
        $this->teachers = DB::table('users')->where('role', 'teacher')->orderBy('name')->get();
        $this->loadTimetables();
    }

    public function loadTimetables()
    {
        // For "Everyday" mode, load Monday's schedule as the unified template
        $dayToLoad = $this->day === 'Everyday' ? 'Monday' : $this->day;
        
        $classIds = $this->classes->pluck('id')->toArray();
        
        $this->timetables = DB::table('timetables')
            ->whereIn('class_id', $classIds)
            ->where('day', $dayToLoad)
            ->where('is_substitute', false)
            ->get();
    }

    public function getScheduleByClass($classId, $periodNo)
    {
        return $this->timetables->where('class_id', $classId)->where('period_no', $periodNo);
    }

    public function getSchedulesByTeacher($teacherId, $periodNo)
    {
        return $this->timetables->where('teacher_id', $teacherId)->where('period_no', $periodNo);
    }

    public function getScheduleSummary()
    {
        $summary = [];
        
        foreach ($this->classes as $class) {
            $classSchedules = $this->timetables->where('class_id', $class->id);
            $assignedPeriods = $classSchedules->count();
            $regularPeriods = $this->periods->where('is_break', false)->where('is_assembly', false)->count();
            
            $summary[] = [
                'class' => $class->name,
                'assigned' => $assignedPeriods,
                'total' => $regularPeriods,
                'percentage' => $regularPeriods > 0 ? round(($assignedPeriods / $regularPeriods) * 100) : 0,
            ];
        }
        
        return $summary;
    }

    #[Layout('components.layouts.empty')] 
    public function render()
    {
        return view('livewire.admin.print-schedule');
    }
}
