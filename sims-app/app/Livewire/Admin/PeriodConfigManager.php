<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\PeriodConfig;

class PeriodConfigManager extends Component
{
    public $groupedPeriods = [];
    public $scheduleTemplates = [];
    public $selectedTemplateId;
    public $activeTemplateId;
    
    public $showModal = false;
    public $editingPeriodNo = null; // Changed from editingId to periodNo for grouping
    
    // Form
    public $p_period_no;
    public $p_label;
    public $p_is_break = false;
    public $p_is_assembly = false;
    public $p_standard_start;
    public $p_standard_end;
    public $p_friday_start;
    public $p_friday_end;

    protected $rules = [
        'p_period_no' => 'required|integer|min:0',
        'p_label' => 'nullable|string|max:50',
    ];

    public $isSaturdayWorking = true;

    public function mount()
    {
        // Load Templates
        $this->scheduleTemplates = \App\Models\ScheduleTemplate::all();
        $activeTemplate = $this->scheduleTemplates->where('is_active', true)->first();
        
        $this->activeTemplateId = $activeTemplate?->id;
        // Default to active or first, similar to ScheduleManager
        $this->selectedTemplateId = $this->activeTemplateId ?? $this->scheduleTemplates->first()?->id;

        $this->loadPeriods();
    }
    
    public function updatedSelectedTemplateId()
    {
        $this->loadPeriods();
    }
    
    public function updatedIsSaturdayWorking()
    {
        $template = \App\Models\ScheduleTemplate::find($this->selectedTemplateId);
        if ($template) {
            $template->update(['is_saturday_working' => $this->isSaturdayWorking]);
            session()->flash('message', 'Saturday settings updated.');
        }
    }

    public function loadPeriods()
    {
        // Sync saturday working status from DB
        $template = \App\Models\ScheduleTemplate::find($this->selectedTemplateId);
        if ($template) {
            $this->isSaturdayWorking = $template->is_saturday_working;
        }

        $all = PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)
            ->orderBy('period_no')
            ->get();
            
        // Group by period_no to merge Standard + Friday overrides
        $grouped = [];
        $processedIds = [];

        foreach ($all as $p) {
            if (in_array($p->id, $processedIds)) continue;

            $groupKey = $p->period_no;
            
            // Initialize entry
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'period_no' => $p->period_no,
                    'label' => $p->label,
                    'is_break' => $p->is_break,
                    'is_assembly' => $p->is_assembly,
                    // Standard Config (Non-Friday or Global)
                    'standard' => null,
                    // Friday Config (Specific)
                    'friday' => null,
                ];
            }

            // Classify this config
            $days = $p->days ?? [];
            if (!empty($days) && count($days) === 1 && in_array('Friday', $days)) {
                $grouped[$groupKey]['friday'] = $p;
            } else {
                $grouped[$groupKey]['standard'] = $p;
                // Update primary labels from standard
                $grouped[$groupKey]['label'] = $p->label;
                $grouped[$groupKey]['is_break'] = $p->is_break;
                $grouped[$groupKey]['is_assembly'] = $p->is_assembly;
            }
            
            $processedIds[] = $p->id;
        }

        $this->groupedPeriods = array_values($grouped);
    }

    public function openModal($periodNo = null)
    {
        $this->resetForm();
        
        if (!is_null($periodNo)) {
            $this->editingPeriodNo = $periodNo;
            // Find group data
            $group = collect($this->groupedPeriods)->firstWhere('period_no', $periodNo);
            
            if ($group) {
                $this->p_period_no = $group['period_no'];
                $this->p_label = $group['label'];
                $this->p_is_break = (bool)$group['is_break'];
                $this->p_is_assembly = (bool)$group['is_assembly'];
                
                // Handle Livewire hydration where models might become arrays
                $standard = $group['standard'];
                if (is_object($standard)) {
                    $this->p_standard_start = $standard->start_time;
                    $this->p_standard_end = $standard->end_time;
                } elseif (is_array($standard)) {
                    $this->p_standard_start = $standard['start_time'] ?? null;
                    $this->p_standard_end = $standard['end_time'] ?? null;
                }

                $friday = $group['friday'];
                if (is_object($friday)) {
                    $this->p_friday_start = $friday->start_time;
                    $this->p_friday_end = $friday->end_time;
                } elseif (is_array($friday)) {
                    $this->p_friday_start = $friday['start_time'] ?? null;
                    $this->p_friday_end = $friday['end_time'] ?? null;
                }
            }
        } else {
            // New period
            $maxPeriod = collect($this->groupedPeriods)->max('period_no') ?? 0;
            $this->p_period_no = $maxPeriod + 1;
            $this->p_label = 'Period ' . ($maxPeriod + 1);
            $this->editingPeriodNo = 'new';
        }
        
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingPeriodNo = null;
        $this->p_period_no = '';
        $this->p_label = '';
        $this->p_is_break = false;
        $this->p_is_assembly = false;
        $this->p_standard_start = '';
        $this->p_standard_end = '';
        $this->p_friday_start = '';
        $this->p_friday_end = '';
    }

    public function save()
    {
        $this->validate();

        \Illuminate\Support\Facades\DB::transaction(function() {
             $standardDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday'];
             
             // Check constraints (skip check if new or same)
             // ...

             if ($this->editingPeriodNo !== 'new') {
                 PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)
                    ->where('period_no', $this->editingPeriodNo)
                    ->delete();
             }

             $periodNo = (int)$this->p_period_no;
             
             // Save Standard
             if ($this->p_standard_start && $this->p_standard_end) {
                 PeriodConfig::create([
                     'schedule_template_id' => $this->selectedTemplateId,
                     'period_no' => $periodNo,
                     'label' => $this->p_label,
                     'start_time' => $this->p_standard_start,
                     'end_time' => $this->p_standard_end,
                     'is_break' => (bool)$this->p_is_break,
                     'is_assembly' => (bool)$this->p_is_assembly,
                     'days' => $this->p_friday_start ? $standardDays : null,
                 ]);
             }

             // Save Friday Override
             if ($this->p_friday_start && $this->p_friday_end) {
                 PeriodConfig::create([
                     'schedule_template_id' => $this->selectedTemplateId,
                     'period_no' => $periodNo,
                     'label' => $this->p_label,
                     'start_time' => $this->p_friday_start,
                     'end_time' => $this->p_friday_end,
                     'is_break' => (bool)$this->p_is_break,
                     'is_assembly' => (bool)$this->p_is_assembly,
                     'days' => ['Friday'],
                 ]);
             }
        });

        session()->flash('message', 'Period configuration saved.');
        $this->closeModal();
        $this->loadPeriods();
    }

    public function delete($periodNo)
    {
        PeriodConfig::where('schedule_template_id', $this->selectedTemplateId)
            ->where('period_no', $periodNo)
            ->delete();
            
        session()->flash('message', 'Period deleted.');
        $this->loadPeriods();
    }

    public function render()
    {
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.period-config-manager')->layout($layout, ['title' => 'Period Configuration']);
    }
}
