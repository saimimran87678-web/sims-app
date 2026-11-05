<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\Substitution;
use App\Services\LicenseStatus;
use Carbon\Carbon;

class ScheduleView extends Component
{
    public $selectedDay = 'Monday';
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    public $scheduleType = 'day_wise';
    public $targetDate = '';
    public $isCurrentDateToday = false;
    
    public $periods = [];
    public $classes = [];
    public $timetables = [];
    public $assignedSubstitutions = [];
    public $relievedSubstitutions = [];

    public function mount()
    {
        // 1. Determine supported days
        $includeSaturday = \App\Models\Setting::get('include_saturday_in_schedule', 'no') === 'yes';
        $this->days = $includeSaturday
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        // 2. Set current day as default
        $dayOfWeek = now()->dayOfWeek;
        $dayMap = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => ($includeSaturday ? 'Saturday' : 'Monday'),
            0 => 'Monday',
        ];
        $this->selectedDay = $dayMap[$dayOfWeek] ?? 'Monday';
        
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $this->scheduleType = LicenseStatus::getEffectiveScheduleType();

        $this->periods = PeriodConfig::where('shift_type', $shiftType)->orderBy('period_no')->get();

        $this->classes = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $activeSessionId)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('shift_type', $shiftType);
            })
            ->orderBy('numeric_value')
            ->get();

        $this->loadTimetables();
    }

    public function calculateDateForDay(string $dayName): string
    {
        $dayOffsets = [
            'Monday' => 0,
            'Tuesday' => 1,
            'Wednesday' => 2,
            'Thursday' => 3,
            'Friday' => 4,
            'Saturday' => 5,
            'Sunday' => 6,
        ];
        $offset = $dayOffsets[$dayName] ?? 0;
        return now()->startOfWeek(Carbon::MONDAY)->addDays($offset)->format('Y-m-d');
    }

    public function loadTimetables()
    {
        $teacherId = Auth::id();
        
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        // Schedule Type Sync:
        // When 'single_schedule' (Universal Schedule) is active, master timetable is stored under 'Monday'
        $this->scheduleType = LicenseStatus::getEffectiveScheduleType();
        $dayToLoad = ($this->scheduleType === 'single_schedule') ? 'Monday' : $this->selectedDay;

        $this->timetables = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->where('classes.academic_session_id', $activeSessionId)
            ->where('timetables.teacher_id', $teacherId)
            ->where('timetables.day', $dayToLoad)
            ->when($shiftType !== 'both', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select('timetables.*')
            ->get();

        // Calculate specific date for the selected day in current week
        $this->targetDate = $this->calculateDateForDay($this->selectedDay);
        $this->isCurrentDateToday = ($this->targetDate === now()->format('Y-m-d'));

        // Query arrangements (substitutions) for target date
        $this->assignedSubstitutions = Substitution::where('date', $this->targetDate)
            ->where('substitute_teacher_id', $teacherId)
            ->with(['classRoom', 'subject', 'absentTeacher', 'timetable'])
            ->get()
            ->keyBy('period_no');

        $this->relievedSubstitutions = Substitution::where('date', $this->targetDate)
            ->where('absent_teacher_id', $teacherId)
            ->with(['classRoom', 'subject', 'substituteTeacher', 'timetable'])
            ->get()
            ->keyBy('period_no');
    }

    public function updatedSelectedDay()
    {
        $this->loadTimetables();
    }

    public function jumpToToday()
    {
        $dayOfWeek = now()->dayOfWeek;
        $dayMap = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => in_array('Saturday', $this->days) ? 'Saturday' : 'Monday',
            0 => 'Monday',
        ];
        $this->selectedDay = $dayMap[$dayOfWeek] ?? 'Monday';
        $this->loadTimetables();
    }

    public function getClassScheduleByPeriod($periodNo)
    {
        return $this->timetables->where('period_no', $periodNo)->first();
    }

    public function getPeriodScheduleData($periodNo): array
    {
        $regular = $this->getClassScheduleByPeriod($periodNo);
        $assigned = $this->assignedSubstitutions[$periodNo] ?? null;
        $relieved = $this->relievedSubstitutions[$periodNo] ?? null;

        $isOverride = ($regular !== null && $assigned !== null);
        $isRelieved = ($regular !== null && $relieved !== null);

        return [
            'regular' => $regular,
            'assigned_arrangement' => $assigned,
            'relieved_arrangement' => $relieved,
            'is_override' => $isOverride,
            'is_relieved' => $isRelieved,
            'has_arrangement' => ($assigned !== null),
        ];
    }

    public function getArrangementCountProperty(): int
    {
        return count($this->assignedSubstitutions);
    }

    public function getOverrideCountProperty(): int
    {
        $count = 0;
        foreach ($this->assignedSubstitutions as $periodNo => $sub) {
            if ($this->getClassScheduleByPeriod($periodNo)) {
                $count++;
            }
        }
        return $count;
    }

    public function getRelievedCountProperty(): int
    {
        return count($this->relievedSubstitutions);
    }

    public function getSummaryStatsProperty(): array
    {
        $regularCount = $this->timetables->count();
        $assignedCount = count($this->assignedSubstitutions);
        $relievedCount = count($this->relievedSubstitutions);
        $overrideCount = $this->overrideCount;

        $teachingPeriodsCount = 0;
        $freePeriodsCount = 0;

        foreach ($this->periods as $period) {
            if ($period->is_break || $period->is_assembly) {
                continue;
            }

            $pNo = $period->period_no;
            $hasRegular = $this->getClassScheduleByPeriod($pNo) !== null;
            $hasAssigned = isset($this->assignedSubstitutions[$pNo]);
            $isRelieved = isset($this->relievedSubstitutions[$pNo]);

            if ($hasAssigned || ($hasRegular && !$isRelieved)) {
                $teachingPeriodsCount++;
            } else {
                $freePeriodsCount++;
            }
        }

        return [
            'regular_classes' => $regularCount,
            'teaching_classes' => $teachingPeriodsCount,
            'arrangement_duties' => $assignedCount,
            'relieved_periods' => $relievedCount,
            'override_count' => $overrideCount,
            'free_periods' => $freePeriodsCount,
        ];
    }

    public function render()
    {
        $dayDates = [];
        foreach ($this->days as $day) {
            $dayDates[$day] = $this->calculateDateForDay($day);
        }

        $scheduleDataByPeriod = [];
        foreach ($this->periods as $period) {
            $scheduleDataByPeriod[$period->period_no] = $this->getPeriodScheduleData($period->period_no);
        }

        $subjectIds = $this->timetables->pluck('subject_id')->filter()->unique();
        $subjects = Subject::whereIn('id', $subjectIds)->get()->keyBy('id');

        return view('livewire.teacher.schedule-view', [
            'dayDates' => $dayDates,
            'arrangementCount' => $this->arrangementCount,
            'overrideCount' => $this->overrideCount,
            'relievedCount' => $this->relievedCount,
            'scheduleDataByPeriod' => $scheduleDataByPeriod,
            'summaryStats' => $this->summaryStats,
            'subjects' => $subjects,
        ])->layout('components.layouts.teacher', ['title' => 'My Schedule']);
    }
}
