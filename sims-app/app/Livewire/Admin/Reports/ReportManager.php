<?php

namespace App\Livewire\Admin\Reports;

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
        // Detect which layout to use based on route
        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.reports.report-manager')->layout($layout, ['title' => 'Reports']);
    }
}
