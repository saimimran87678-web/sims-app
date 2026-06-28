<?php

namespace App\Livewire\Admin\Fee;

use App\Models\Student;
use App\Models\FeeRecord;
use App\Models\FeePayment;
use App\Models\AcademicSession;
use Livewire\Component;
use Livewire\Attributes\On;

class StudentLedger extends Component
{
    public $student;
    public $studentId;

    #[On('refreshFeeRecords')]
    public function refreshLedger()
    {
        // Refreshes view dynamically when payment is recorded
    }

    public function mount($studentId)
    {
        $this->studentId = $studentId;
        $this->student = Student::with('class')->findOrFail($studentId);
    }

    public function render()
    {
        $sessionId = AcademicSession::getActiveSessionId();

        $records = FeeRecord::with(['items', 'payments'])
            ->where('student_id', $this->studentId)
            ->where('academic_session_id', $sessionId)
            ->orderBy('period', 'desc')
            ->get();

        // Calculate totals
        $totalBilled = $records->sum('total_amount');
        $totalPaid = $records->sum('paid_amount');
        $totalBalance = $records->where('period', '<=', now()->format('Y-m'))->sum('balance');

        return view('livewire.admin.fee.student-ledger', [
            'records' => $records,
            'totalBilled' => $totalBilled,
            'totalPaid' => $totalPaid,
            'totalBalance' => $totalBalance,
        ])->layout('components.layouts.admin');
    }
}
