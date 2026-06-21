<?php

namespace App\Livewire\Admin\Fee;

use App\Models\Student;
use App\Models\FeeRecord;
use App\Models\FeePayment;
use App\Models\AcademicSession;
use Livewire\Component;

class StudentLedger extends Component
{
    public $student;
    public $studentId;

    public function mount($studentId)
    {
        $this->studentId = $studentId;
        $this->student = Student::with('class')->findOrFail($studentId);
    }

    public function render()
    {
        $sessionId = AcademicSession::getActiveSessionId();

        $records = FeeRecord::with('items')
            ->where('student_id', $this->studentId)
            ->where('academic_session_id', $sessionId)
            ->orderBy('period', 'desc')
            ->get();

        $payments = FeePayment::with('record')
            ->where('student_id', $this->studentId)
            ->where('academic_session_id', $sessionId)
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals
        $totalBilled = $records->sum('total_amount');
        $totalPaid = $records->sum('paid_amount');
        $totalBalance = $records->where('period', '<=', now()->format('Y-m'))->sum('balance');

        return view('livewire.admin.fee.student-ledger', [
            'records' => $records,
            'payments' => $payments,
            'totalBilled' => $totalBilled,
            'totalPaid' => $totalPaid,
            'totalBalance' => $totalBalance,
        ])->layout('components.layouts.admin');
    }
}
