<?php

namespace App\Livewire\Teacher\Reports;

use Livewire\Component;

class ReportManager extends Component
{
    public $activeTab = 'attendance';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.teacher.reports.report-manager')->layout('components.layouts.teacher', ['title' => 'Reports']);
    }
}
