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

    #[Url]
    public $date;

    public $arrangements = [];
    public $dailyNote = '';

    public function mount()
    {
        if (!$this->date) {
            $this->date = now()->format('Y-m-d');
        }

        $this->periods = PeriodConfig::orderBy('period_no')->get();
        $this->classes = Classes::orderBy('numeric_value')->get();
        $this->teachers = DB::table('users')->where('role', 'teacher')->orderBy('name')->get();
        $this->loadTimetables();
    }

    public function loadTimetables()
    {
        if ($this->viewType === 'arrangements') {
             // Load Daily Note
            $noteRecord = DB::table('daily_notes')->where('date', $this->date)->first();
            $this->dailyNote = $noteRecord ? $noteRecord->note : '';

            $rawArrangements = DB::table('timetables')
                ->where('is_substitute', true)
                ->where('substitute_date', $this->date)
                ->get();
            
            $dayName = \Carbon\Carbon::parse($this->date)->format('l');
            $regular = DB::table('timetables')
                ->where('day', $dayName)
                ->where('is_substitute', false)
                ->get();

            $this->arrangements = $rawArrangements->map(function($sub) use ($regular) {
                // Find regular teacher
                $reg = $regular->where('class_id', $sub->class_id)
                               ->where('period_no', $sub->period_no)
                               ->first();
                $sub->absent_teacher_id = $reg ? $reg->teacher_id : null;
                return $sub;
            })->groupBy('absent_teacher_id');

            // Sort
             $teachers = $this->teachers;
             $this->arrangements = $this->arrangements->sortBy(function ($subs, $key) use ($teachers) {
                if (!$key) return 'ZZZZ';
                $t = $teachers->firstWhere('id', $key);
                return $t ? $t->name : 'ZZZZ';
            });

            return;
        }

        // For "Everyday" mode, load Monday's schedule as the unified template
        $dayToLoad = $this->day === 'Everyday' ? 'Monday' : $this->day;
        
        $this->timetables = DB::table('timetables')
            ->where('day', $dayToLoad)
            ->where('is_substitute', false)
            ->get();
    }

    public function getScheduleByClass($classId, $periodNo)
    {
        return $this->timetables->where('class_id', $classId)->where('period_no', $periodNo)->first();
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
