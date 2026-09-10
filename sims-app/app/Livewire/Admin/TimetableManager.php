<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ScheduleTemplate;
use App\Models\PeriodConfig;
use App\Models\Classes;
use App\Models\User;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Support\Facades\DB;

class TimetableManager extends Component
{
    // View State
    public $viewMode = 'class'; // 'class' or 'teacher'
    public $selectedTemplateId;
    public $selectedDay = 'Monday'; // Default day
    
    // Data Collections
    public $templates = [];
    public $periods = [];
    public $classes = [];
    public $teachers = [];
    public $subjects = []; // All subjects (for grid lookup)
    public $classSubjects = []; // Filtered subjects (for modal dropdown)
    public $timetables = []; 

    // Modal State
    public $showModal = false;
    public $modalDay;
    public $modalPeriodNo;
    public $modalRowId; // Class ID or Teacher ID depending on viewMode
    public $modalTitle = ''; 

    // Timings Modal State
    public $showTimingsModal = false;
    public $periodTimings = [];

    // Form Fields - Dynamic Entries for Divided/Merged Classes
    public $entries = []; 
    // Format: [['id' => null, 'teacher_id' => '', 'class_id' => '', 'subject_id' => '', 'room' => '', 'merged_class_id' => '']]
    public $syncAllDays = true;

    protected $rules = [
        'selectedTemplateId' => 'required',
        'selectedDay' => 'required',
        'entries.*.subject_id' => 'required',
    ];

    public function mount()
    {
        $this->templates = ScheduleTemplate::where('is_active', true)->get();
        // Fallback to first if no active?
        if ($this->templates->isEmpty()) {
            $this->templates = ScheduleTemplate::all();
        }
        
        // Fix: Ensure selectedTemplateId is set if templates exist
        if ($this->templates->isNotEmpty()) {
             $this->selectedTemplateId = $this->templates->first()->id;
        } else {

        }

        // Fixed Timetable: Default to today's weekday (fall back to Monday on Sunday)
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $this->selectedDay = in_array(now()->dayName, $weekdays) ? now()->dayName : 'Monday';

        $this->loadInitialData();
        $this->loadSchedule();
    }

    public function updatedSelectedDay()
    {
        // View update only, data is already loaded for the template
    }

    public function updatedSelectedTemplateId()
    {
        $this->loadSchedule();
    }

    public function updatedViewMode()
    {
        $this->loadSchedule();
    }

    public function loadInitialData()
    {
        $this->classes = Classes::orderBy('numeric_value')->orderBy('name')->get();
        $this->teachers = User::where('role', 'teacher')->orderBy('name')->get();
        // Grid needs ALL subjects to display names
        $this->subjects = Subject::all(); 
        $this->classSubjects = collect();
    }

    // Load subjects for a specific class
    public function getSubjectsForClass($classId)
    {
        if (!$classId) return collect();
        return Subject::where('class_id', $classId)->orderBy('name')->get();
    }

    public function loadSchedule()
    {
        if (!$this->selectedTemplateId) return;

        // Load Periods for this Template
        $this->periods = PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)
            ->orderBy('period_no')
            ->get();

        // Load Timetables
        // We load ALL timetables for this template to check conflicts easily
        $this->timetables = Timetable::where('schedule_template_id', $this->selectedTemplateId)
            ->get();
    }

    public function openModal($day, $periodNo, $rowId)
    {
        $this->resetForm();
        $this->modalDay = $day;
        $this->modalPeriodNo = $periodNo;
        $this->modalRowId = $rowId;

        // Populate existing entries
        if ($this->viewMode === 'class') {
            $class = $this->classes->firstWhere('id', $rowId);
            $this->modalTitle = "{$class->name} - $day Period $periodNo";
            
            // Subjects are fixed for the current class in Class View
            $this->classSubjects = $this->getSubjectsForClass($rowId);

            $existing = collect($this->timetables)->filter(function ($item) use ($rowId, $day, $periodNo) {
                // Return entries where this class is either primary or merged
                return ($item->class_id == $rowId || $item->merged_class_id == $rowId)
                    && $item->day === $day
                    && $item->period_no == $periodNo;
            })->values();

        } else {
            $teacher = $this->teachers->firstWhere('id', $rowId);
            $this->modalTitle = "{$teacher->name} - $day Period $periodNo";
            
            $existing = collect($this->timetables)->filter(function ($item) use ($rowId, $day, $periodNo) {
                return $item->teacher_id == $rowId
                    && $item->day === $day
                    && $item->period_no == $periodNo;
            })->values();
        }

        if ($existing->isNotEmpty()) {
            foreach ($existing as $entry) {
                // If we clicked from merged_class_id, we need to show the primary class_id 
                // in the form so it makes sense (or rather swap them if we want to be smart, 
                // but just showing the primary class is best).
                $this->entries[] = [
                    'id' => $entry->id,
                    'teacher_id' => $entry->teacher_id,
                    'class_id' => $entry->class_id,
                    'subject_id' => $entry->subject_id,
                    'room' => $entry->room,
                    'merged_class_id' => $entry->merged_class_id,
                ];
            }
        } else {
            // Setup an empty first entry
            $this->addEntry();
        }

        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->entries = [];
        $this->classSubjects = collect(); // Reset
        $this->syncAllDays = true;
        $this->resetValidation();
    }

    public function addEntry()
    {
        $this->entries[] = [
            'id' => null,
            'teacher_id' => '',
            'class_id' => $this->viewMode === 'class' ? $this->modalRowId : '',
            'subject_id' => '',
            'room' => '',
            'merged_class_id' => '',
        ];
    }

    public function removeEntry($index)
    {
        unset($this->entries[$index]);
        $this->entries = array_values($this->entries); // Re-index

        // If empty, close the modal
        if (empty($this->entries)) {
            $this->showModal = false;
        }
    }

    public function clearPeriod()
    {
        $targetDays = $this->syncAllDays
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : [$this->modalDay];

        DB::transaction(function () use ($targetDays) {
            foreach ($targetDays as $day) {
                $query = Timetable::where('schedule_template_id', $this->selectedTemplateId)
                    ->where('day', $day)
                    ->where('period_no', $this->modalPeriodNo);

                if ($this->viewMode === 'class') {
                    $query->where(function ($q) {
                        $q->where('class_id', $this->modalRowId)
                          ->orWhere('merged_class_id', $this->modalRowId);
                    });
                } else {
                    $query->where('teacher_id', $this->modalRowId);
                }

                $query->delete();
            }
        });

        $this->showModal = false;
        $this->loadSchedule();
        session()->flash('message', 'Period cleared successfully.');
    }

    public function save()
    {
        $this->validate();

        $errorsCount = 0;

        foreach ($this->entries as $index => $formData) {
            $teacherId = $formData['teacher_id'] ?: null;
            $classId = $formData['class_id'] ?: null;
            $subjectId = $formData['subject_id'] ?: null;
            $room = $formData['room'] ?: null;
            $mergedId = $formData['merged_class_id'] ?: null;

            if (!$teacherId) {
                $this->addError("entries.{$index}.teacher_id", 'Teacher is required.');
                $errorsCount++;
            }
            if (!$classId) {
                $this->addError("entries.{$index}.class_id", 'Class is required.');
                $errorsCount++;
            }

            if ($errorsCount > 0) continue;

            // 1. Conflict Check: Is Teacher Busy?
            $teacherBusy = Timetable::where('schedule_template_id', $this->selectedTemplateId)
                ->where('day', $this->modalDay)
                ->where('period_no', $this->modalPeriodNo)
                ->where('teacher_id', $teacherId)
                ->where('class_id', '!=', $classId) // allow same teacher in same class (e.g. double subject?)
                ->where(function ($q) use ($formData) {
                    if (!empty($formData['id'])) {
                        $q->where('id', '!=', $formData['id']);
                    }
                })
                ->exists();

            if ($teacherBusy) {
                $this->addError("entries.{$index}.teacher_id", 'This teacher is already assigned to another class at this time.');
                $errorsCount++;
            }

            // 2. Conflict Check: Is Class Occupied? 
            if ($this->viewMode === 'teacher') {
                 $classBusy = Timetable::where('schedule_template_id', $this->selectedTemplateId)
                    ->where('day', $this->modalDay)
                    ->where('period_no', $this->modalPeriodNo)
                    ->where('class_id', $classId)
                    ->where(function ($q) use ($formData) {
                        if (!empty($formData['id'])) {
                            $q->where('id', '!=', $formData['id']);
                        }
                    })
                    ->exists();
            }
        }

        if ($errorsCount > 0) {
            return;
        }

        $targetDays = $this->syncAllDays
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : [$this->modalDay];

        DB::transaction(function () use ($targetDays) {
            foreach ($targetDays as $day) {
                if ($this->viewMode === 'class') {
                    $dayRecords = Timetable::where('schedule_template_id', $this->selectedTemplateId)
                        ->where('day', $day)
                        ->where('period_no', $this->modalPeriodNo)
                        ->where(function ($q) {
                            $q->where('class_id', $this->modalRowId)
                              ->orWhere('merged_class_id', $this->modalRowId);
                        })
                        ->get();
                } else {
                    $dayRecords = Timetable::where('schedule_template_id', $this->selectedTemplateId)
                        ->where('day', $day)
                        ->where('period_no', $this->modalPeriodNo)
                        ->where('teacher_id', $this->modalRowId)
                        ->get();
                }

                foreach ($this->entries as $idx => $formData) {
                    $mergedId = $formData['merged_class_id'] ?: null;
                    if ($mergedId == $formData['class_id']) {
                        $mergedId = null;
                    }

                    $existing = $dayRecords->get($idx);
                    if ($existing) {
                        $existing->update([
                            'class_id' => $formData['class_id'],
                            'teacher_id' => $formData['teacher_id'],
                            'subject_id' => $formData['subject_id'],
                            'room' => $formData['room'] ?: null,
                            'merged_class_id' => $mergedId,
                            'is_divided' => count($this->entries) > 1,
                        ]);
                    } else {
                        Timetable::create([
                            'schedule_template_id' => $this->selectedTemplateId,
                            'day' => $day,
                            'period_no' => $this->modalPeriodNo,
                            'class_id' => $formData['class_id'],
                            'teacher_id' => $formData['teacher_id'],
                            'subject_id' => $formData['subject_id'],
                            'room' => $formData['room'] ?: null,
                            'merged_class_id' => $mergedId,
                            'is_divided' => count($this->entries) > 1,
                        ]);
                    }
                }

                if ($dayRecords->count() > count($this->entries)) {
                    for ($i = count($this->entries); $i < $dayRecords->count(); $i++) {
                        $dayRecords[$i]->delete();
                    }
                }
            }
        });

        $this->showModal = false;
        $this->loadSchedule(); // Refresh grid
        session()->flash('message', 'Schedule updated successfully.');
    }

    public function deleteAllEntries()
    {
        if ($this->selectedTemplateId) {
            Timetable::where('schedule_template_id', $this->selectedTemplateId)->delete();
            $this->loadSchedule();
            session()->flash('message', 'All entries for the selected template have been deleted.');
        }
    }

    public function editTimings()
    {
        $this->periodTimings = [];
        foreach ($this->periods as $period) {
            $this->periodTimings[] = [
                'id' => $period->id,
                'label' => $period->label,
                'start_time' => $period->start_time ? clone $period->start_time : null,
                'end_time' => $period->end_time ? clone $period->end_time : null,
            ];
        }
        // format them to H:i
        foreach ($this->periodTimings as &$pt) {
            $pt['start_time'] = $pt['start_time'] ? $pt['start_time']->format('H:i') : '';
            $pt['end_time'] = $pt['end_time'] ? $pt['end_time']->format('H:i') : '';
        }
        
        $this->showTimingsModal = true;
    }

    public function saveTimings()
    {
        // validate and save
        $this->validate([
            'periodTimings.*.start_time' => 'required',
            'periodTimings.*.end_time' => 'required',
        ]);

        foreach ($this->periodTimings as $pt) {
            PeriodConfig::where('id', $pt['id'])->update([
                'start_time' => $pt['start_time'],
                'end_time' => $pt['end_time'],
            ]);
        }

        $this->showTimingsModal = false;
        $this->loadSchedule();
        session()->flash('message', 'Period timings updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.timetable-manager')
            ->layout('components.layouts.admin', ['title' => 'Timetable Management']);
    }

    // Helper to get Data for Cell as a Collection
    public function getCellData($rowId, $day, $periodNo)
    {
        if ($this->viewMode === 'class') {
            return collect($this->timetables)->filter(function ($item) use ($rowId, $day, $periodNo) {
                return ($item->class_id == $rowId || $item->merged_class_id == $rowId)
                    && $item->day === $day
                    && $item->period_no == $periodNo;
            })->values();
        } else {
            return collect($this->timetables)->filter(function ($item) use ($rowId, $day, $periodNo) {
                return $item->teacher_id == $rowId
                    && $item->day === $day
                    && $item->period_no == $periodNo;
            })->values();
        }
    }
}
