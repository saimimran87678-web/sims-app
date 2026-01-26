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

    public $arrangementDate;
    public $arrangements = [];
    public $dailyNote = '';

    public function mount()
    {
        $this->arrangementDate = now()->format('Y-m-d');
        $this->periods = PeriodConfig::orderBy('period_no')->get();
        $this->classes = Classes::orderBy('numeric_value')->get();
        $this->teachers = DB::table('users')->where('role', 'teacher')->orderBy('name')->get();
        $this->loadTimetables();
    }

    public function loadTimetables()
    {
        if ($this->viewType === 'arrangements') {
            // Load Daily Note
            $noteRecord = DB::table('daily_notes')->where('date', $this->arrangementDate)->first();
            $this->dailyNote = $noteRecord ? $noteRecord->note : '';

            $rawArrangements = DB::table('timetables')
                ->where('is_substitute', true)
                ->where('substitute_date', $this->arrangementDate)
                ->get();
            
            // Deduce Absent Teachers
            $dayName = \Carbon\Carbon::parse($this->arrangementDate)->format('l');
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

            // Sort by Absent Teacher Name
            $teachers = $this->teachers;
            $this->arrangements = $this->arrangements->sortBy(function ($subs, $key) use ($teachers) {
                if (!$key) return 'ZZZZ';
                $t = $teachers->firstWhere('id', $key);
                return $t ? $t->name : 'ZZZZ';
            });
            
            return;
        }

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

    public function saveDailyNote()
    {
        $exists = DB::table('daily_notes')->where('date', $this->arrangementDate)->exists();
        if ($exists) {
            DB::table('daily_notes')->where('date', $this->arrangementDate)->update(['note' => $this->dailyNote, 'updated_at' => now()]);
        } else {
             DB::table('daily_notes')->insert(['date' => $this->arrangementDate, 'note' => $this->dailyNote, 'created_at' => now(), 'updated_at' => now()]);
        }
        
        session()->flash('message', 'Note saved.');
    }

    public function updatedArrangementDate()
    {
        $this->loadTimetables();
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
        
        $schedules = $this->timetables->where('class_id', $classId)->where('period_no', $periodNo);
        $first = $schedules->first();
        
        if ($first) {
            $teachers = $schedules->map(fn($s) => collect($this->teachers)->firstWhere('id', $s->teacher_id)->name ?? '-')->unique()->join(' | ');
            $subjects = $schedules->map(fn($s) => Subject::find($s->subject_id)->name ?? '-')->unique()->join(' / ');
            
            $class = collect($this->classes)->firstWhere('id', $classId);
            $period = $this->periods->firstWhere('period_no', $periodNo);
            
            $this->detailData = [
                'class_name' => $class->name ?? '-',
                'period_label' => $period->label ?? "Period $periodNo",
                'period_time' => $period ? \Carbon\Carbon::parse($period->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($period->end_time)->format('h:i A') : '-',
                'teacher_name' => $teachers,
                'subject_name' => $subjects,
                'room' => $first->room ?? '-', 
                'is_divided' => $schedules->count() > 1 || $first->is_divided,
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
