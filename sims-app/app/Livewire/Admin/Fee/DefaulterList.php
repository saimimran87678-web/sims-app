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

    public function mount()
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && !$user->hasRole('Super Admin')) {
            abort_if(!$user->can('fees.manage'), 403);
        }
    }

    public function render()
    {
        $sessionId = AcademicSession::getActiveSessionId();
        $sessionObj = AcademicSession::find($sessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        // Get aggregate balance per student
        $query = FeeRecord::with(['student', 'class'])
            ->whereHas('class', function ($q) use ($shiftType) {
                $q->where('shift_type', $shiftType);
            })
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

        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.fee.defaulter-list', [
            'defaulters' => $defaulters,
            'classes' => Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $sessionId)
                ->where('shift_type', $shiftType)
                ->orderBy('numeric_value')
                ->get(),
            'totalDefaulters' => $defaulters->total(),
            'totalDueAggregate' => $query->get()->sum('total_due') // Sum across all pages for the current filter
        ])->layout($layout);
    }
}
