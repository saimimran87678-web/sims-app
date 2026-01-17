<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\Subject;

class ViewSchedule extends Component
{
    public $selectedDay = 'Monday';
    public $viewType = 'class'; // class, teacher, summary, room
    public $cardsPerPage = 4; // Default to 4 cards per page
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    
    public $periods = [];
    public $classes = [];
    public $teachers = [];
    public $timetables = [];
    public $rooms = [];

    // For viewing a specific assignment
    public $showDetailModal = false;
    public $detailClassId;
    public $detailPeriodNo;
    public $detailData = null;

    public function mount()
    {
        $this->periods = PeriodConfig::orderBy('period_no')->get();
        $this->classes = Classes::orderBy('numeric_value')->get();
        $this->teachers = DB::table('users')->where('role', 'teacher')->orderBy('name')->get();
        $this->loadTimetables();
    }

    public function loadTimetables()
    {
        // For "Everyday" mode, load Monday's schedule as the unified template
        $dayToLoad = $this->selectedDay === 'Everyday' ? 'Monday' : $this->selectedDay;
        
        $this->timetables = DB::table('timetables')
            ->where('day', $dayToLoad)
            ->where('is_substitute', false)
            ->get();

        if ($this->viewType === 'room') {
            $this->rooms = $this->timetables->pluck('room')->filter()->unique()->sort()->values();
        }
    }

    public function updatedSelectedDay()
    {
        $this->loadTimetables();
    }

    public function updatedViewType()
    {
        $this->loadTimetables();
    }

    public function getScheduleByClass($classId, $periodNo)
    {
        return $this->timetables->where('class_id', $classId)->where('period_no', $periodNo)->first();
    }

    public function getSchedulesByTeacher($teacherId, $periodNo)
    {
        return $this->timetables->where('teacher_id', $teacherId)->where('period_no', $periodNo);
    }

    public function viewDetail($classId, $periodNo)
    {
        $this->detailClassId = $classId;
        $this->detailPeriodNo = $periodNo;
        
        $schedule = $this->timetables->where('class_id', $classId)->where('period_no', $periodNo)->first();
        if ($schedule) {
            $teacher = collect($this->teachers)->firstWhere('id', $schedule->teacher_id);
            $subject = Subject::find($schedule->subject_id);
            $class = collect($this->classes)->firstWhere('id', $classId);
            $period = $this->periods->firstWhere('period_no', $periodNo);
            
            $this->detailData = [
                'class_name' => $class->name ?? '-',
                'period_label' => $period->label ?? "Period $periodNo",
                'period_time' => $period ? \Carbon\Carbon::parse($period->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($period->end_time)->format('h:i A') : '-',
                'teacher_name' => $teacher->name ?? '-',
                'subject_name' => $subject->name ?? '-',
                'room' => $schedule->room ?? '-',
                'is_divided' => $schedule->is_divided,
            ];
            
            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailData = null;
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

    public function render()
    {
        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.view-schedule')->layout($layout, ['title' => 'View Schedule']);
    }
}
