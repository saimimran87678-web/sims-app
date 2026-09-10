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
    
    // Modal State
    public $showModal = false;
    public $modalDay;
    public $modalPeriodNo;
    public $modalRowId; // Class ID or Teacher ID depending on viewMode
    public $modalTitle = ''; 
    public $classSubjects = []; // Filtered subjects for modal dropdown

    // Timings Modal State
    public $showTimingsModal = false;
    public $periodTimings = [];

    // Form Fields - Dynamic Entries for Divided/Merged Classes
    public $entries = []; 
    // Format: [['id' => null, 'teacher_id' => '', 'class_id' => '', 'subject_id' => '', 'room' => '', 'merged_class_id' => '']]
    public $syncAllDays = true;

    // Protected (not serialized over the wire) pre-indexed grid data
    protected $gridMap = [];

    protected $rules = [
        'selectedTemplateId' => 'required',
        'selectedDay' => 'required',
        'entries.*.subject_id' => 'required',
    ];

    public function mount()
    {
        $templates = ScheduleTemplate::where('is_active', true)->get();
        if ($templates->isEmpty()) {
            $templates = ScheduleTemplate::all();
        }
        
        if ($templates->isNotEmpty()) {
             $this->selectedTemplateId = $templates->first()->id;
        }

        // Fixed Timetable: Default to today's weekday (fall back to Monday on Sunday)
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $this->selectedDay = in_array(now()->dayName, $weekdays) ? now()->dayName : 'Monday';
    }

    public function updatedSelectedDay()
    {
    }

    public function updatedSelectedTemplateId()
    {
    }

    public function updatedViewMode()
    {
    }

    // Load subjects for a specific class
    public function getSubjectsForClass($classId)
    {
        if (!$classId) return collect();
        return Subject::where('class_id', $classId)->orderBy('name')->get();
    }

    public function loadSchedule()
    {
    }

    public function openModal($day, $periodNo, $rowId)
    {
        $this->resetForm();
        $this->modalDay = $day;
        $this->modalPeriodNo = $periodNo;
        $this->modalRowId = $rowId;

        // Populate existing entries
        if ($this->viewMode === 'class') {
            $class = Classes::withoutGlobalScopes()->find($rowId);
            $this->modalTitle = "{$class?->name} - $day Period $periodNo";
            
            // Subjects are fixed for the current class in Class View
            $this->classSubjects = $this->getSubjectsForClass($rowId);

            // Load teacher map for class view dropdown
            $this->teachersById = \App\Models\User::where('role', 'teacher')->get()->keyBy('id');

            $existing = Timetable::where('schedule_template_id', $this->selectedTemplateId)
                ->where('day', $day)
                ->where('period_no', $periodNo)
                ->where(function ($q) use ($rowId) {
                    $q->where('class_id', $rowId)->orWhere('merged_class_id', $rowId);
                })
                ->get();

        } else {
            $teacher = User::find($rowId);
            $this->modalTitle = "{$teacher?->name} - $day Period $periodNo";
            
            $existing = Timetable::where('schedule_template_id', $this->selectedTemplateId)
                ->where('day', $day)
                ->where('period_no', $periodNo)
                ->where('teacher_id', $rowId)
                ->get();
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



    /**
     * Reset modal form state.
     */
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
        // Auto‑assign teacher for Teacher view
        if ($this->viewMode === 'teacher') {
            foreach ($this->entries as &$e) {
                $e['teacher_id'] = $this->modalRowId;
            }
            unset($e);
        }


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
                // Remove any existing entries for the target day/period
                if ($this->viewMode === 'class') {
                    Timetable::where('schedule_template_id', $this->selectedTemplateId)
                        ->where('day', $day)
                        ->where('period_no', $this->modalPeriodNo)
                        ->where(function ($q) {
                            $q->where('class_id', $this->modalRowId)
                              ->orWhere('merged_class_id', $this->modalRowId);
                        })
                        ->delete();
                } else {
                    Timetable::where('schedule_template_id', $this->selectedTemplateId)
                        ->where('day', $day)
                        ->where('period_no', $this->modalPeriodNo)
                        ->where('teacher_id', $this->modalRowId)
                        ->delete();
                }

                // Build rows for bulk insert
                $rows = [];
                foreach ($this->entries as $formData) {
                    $mergedId = $formData['merged_class_id'] ?: null;
                    if ($mergedId == $formData['class_id']) {
                        $mergedId = null;
                    }
                    $rows[] = [
                        'schedule_template_id' => $this->selectedTemplateId,
                        'day' => $day,
                        'period_no' => $this->modalPeriodNo,
                        'class_id' => $formData['class_id'],
                        'teacher_id' => $formData['teacher_id'],
                        'subject_id' => $formData['subject_id'],
                        'room' => $formData['room'] ?: null,
                        'merged_class_id' => $mergedId,
                        'is_divided' => count($this->entries) > 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert all rows in one query
                if (!empty($rows)) {
                    Timetable::insert($rows);
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
        $periods = PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)
            ->orderBy('period_no')
            ->get();
        $this->periodTimings = [];
        foreach ($periods as $period) {
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
        session()->flash('message', 'Period timings updated successfully.');
    }

    public function render()
    {
        $templates = ScheduleTemplate::where('is_active', true)->get();
        if ($templates->isEmpty()) {
            $templates = ScheduleTemplate::all();
        }

        $periods = PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)
            ->orderBy('period_no')
            ->get();

        $classes = Classes::withoutGlobalScopes()->orderBy('numeric_value')->orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $teachersById = $teachers->keyBy('id');
        $subjects = Subject::all()->keyBy('id');
        $subjectsByClass = Subject::all()->groupBy('class_id');
        $classesById = $classes->keyBy('id');
        $teachersById = $teachers->keyBy('id');

        $dayTimetables = Timetable::where('schedule_template_id', $this->selectedTemplateId)
            ->where('day', $this->selectedDay)
            ->get();

        $this->gridMap = [];
        foreach ($dayTimetables as $item) {
            if ($this->viewMode === 'class') {
                $this->gridMap[$item->class_id][$item->period_no][] = $item;
                if ($item->merged_class_id) {
                    $this->gridMap[$item->merged_class_id][$item->period_no][] = $item;
                }
            } else {
                if ($item->teacher_id) {
                    $this->gridMap[$item->teacher_id][$item->period_no][] = $item;
                }
            }
        }

        foreach ($this->gridMap as $rId => &$periodsArr) {
            foreach ($periodsArr as $pNo => &$items) {
                $items = collect($items);
            }
        }
        unset($periodsArr, $items);

        return view('livewire.admin.timetable-manager', [
            'templates' => $templates,
            'periods' => $periods,
            'classes' => $classes,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'subjectsByClass' => $subjectsByClass,
            'classesById' => $classesById,

            'classSubjects' => $this->classSubjects,
            
            'teachersById' => $teachersById,

        ])->layout('components.layouts.admin', ['title' => 'Timetable Management']);
    }

    // Helper to get Data for Cell as a Collection
    public function getCellData($rowId, $day, $periodNo)
    {
        return $this->gridMap[$rowId][$periodNo] ?? collect();
    }
}
