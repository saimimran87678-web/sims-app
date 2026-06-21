<?php

namespace App\Livewire\Admin\Fee;

use App\Models\FeeRecord;
use App\Models\Classes;
use App\Models\AcademicSession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class DefaulterList extends Component
{
    use WithPagination;

    public $filter_class = '';
    public $min_due = 1;

    public function render()
    {
        $sessionId = AcademicSession::getActiveSessionId();

        // Get aggregate balance per student
        $query = FeeRecord::with(['student', 'class'])
            ->where('academic_session_id', $sessionId)
            ->where('balance', '>=', $this->min_due)
            ->where('period', '<=', now()->format('Y-m'))
            ->select('student_id', 'class_id', DB::raw('SUM(balance) as total_due'), DB::raw('COUNT(id) as unpaid_bills'))
            ->groupBy('student_id', 'class_id');

        if ($this->filter_class) {
            $query->where('class_id', $this->filter_class);
        }

        // We paginate over the grouped results
        $defaulters = $query->orderBy('total_due', 'desc')->paginate(15);

        return view('livewire.admin.fee.defaulter-list', [
            'defaulters' => $defaulters,
            'classes' => Classes::where('academic_session_id', $sessionId)->get(),
            'totalDefaulters' => $defaulters->total(),
            'totalDueAggregate' => $query->get()->sum('total_due') // Sum across all pages for the current filter
        ])->layout('components.layouts.admin');
    }
}
