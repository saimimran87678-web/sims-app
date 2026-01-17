<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\PeriodConfig;

class PeriodConfigManager extends Component
{
    public $periods = [];
    public $showModal = false;
    public $editingId = null;
    
    // Form
    public $period_no;
    public $start_time;
    public $end_time;
    public $is_break = false;
    public $is_assembly = false;
    public $label;

    protected $rules = [
        'period_no' => 'required|integer|min:0',
        'start_time' => 'required',
        'end_time' => 'required',
        'is_break' => 'boolean',
        'is_assembly' => 'boolean',
        'label' => 'nullable|string|max:50',
    ];

    public function mount()
    {
        $this->loadPeriods();
    }

    public function loadPeriods()
    {
        $this->periods = PeriodConfig::orderBy('period_no')->get();
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        
        if ($id) {
            $period = PeriodConfig::find($id);
            if ($period) {
                $this->editingId = $id;
                $this->period_no = $period->period_no;
                $this->start_time = $period->start_time;
                $this->end_time = $period->end_time;
                $this->is_break = $period->is_break;
                $this->is_assembly = $period->is_assembly ?? false;
                $this->label = $period->label;
            }
        } else {
            // New period - auto-increment period_no
            $maxPeriod = PeriodConfig::max('period_no') ?? 0;
            $this->period_no = $maxPeriod + 1;
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
        $this->editingId = null;
        $this->period_no = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->is_break = false;
        $this->is_assembly = false;
        $this->label = '';
    }

    public function save()
    {
        $this->validate();

        $defaultLabel = $this->is_assembly ? 'Assembly' : ($this->is_break ? 'Break' : 'Period ' . $this->period_no);
        $data = [
            'period_no' => $this->period_no,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_break' => $this->is_break,
            'is_assembly' => $this->is_assembly,
            'label' => $this->label ?: $defaultLabel,
        ];

        if ($this->editingId) {
            PeriodConfig::find($this->editingId)->update($data);
            session()->flash('message', 'Period updated successfully!');
        } else {
            PeriodConfig::create($data);
            session()->flash('message', 'Period added successfully!');
        }

        $this->closeModal();
        $this->loadPeriods();
    }

    public function delete($id)
    {
        PeriodConfig::destroy($id);
        session()->flash('message', 'Period deleted.');
        $this->loadPeriods();
    }

    public function render()
    {
        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.period-config-manager')->layout($layout, ['title' => 'Period Configuration']);
    }
}
