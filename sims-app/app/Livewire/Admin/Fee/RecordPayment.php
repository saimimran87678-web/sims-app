<?php

namespace App\Livewire\Admin\Fee;

use App\Models\FeeRecord;
use App\Models\FeePayment;
use Livewire\Component;
use Livewire\Attributes\On;

class RecordPayment extends Component
{
    public $isOpen = false;
    public $recordId;
    public $record;

    // Search and select student properties (for standalone page mode)
    public $search = '';
    public $selectedStudentId = null;
    public $selectedStudent = null;
    public $unpaidRecords = [];

    public $payment_date;
    public $amount;
    public $payment_method = 'cash';
    public $reference_number = '';
    public $remarks = '';
    public $filter_class = '';
    public $filter_status = 'active';
    public $isPage = false;
    public $itemPayments = [];
    public $lastReceipt = null;
    public $showReceiptModal = false;

    public function mount()
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && !$user->hasRole('Super Admin')) {
            abort_if(!$user->can('fees.manage'), 403);
        }
        $this->isPage = request()->routeIs('admin.fee.record-payment') || request()->routeIs('teacher.shared.fee.record-payment');
    }

    public function render()
    {
        $students = [];
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($sessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        if (!empty(trim($this->search)) || $this->filter_class || $this->filter_status === 'archived') {
            if ($this->filter_status === 'archived') {
                $studentsQuery = \App\Models\Student::onlyTrashed()->with('class');
            } elseif ($this->filter_status === 'all') {
                $studentsQuery = \App\Models\Student::withTrashed()->with('class');
            } else {
                $studentsQuery = \App\Models\Student::with('class');
                if ($this->filter_status) {
                    $studentsQuery->where('status', $this->filter_status);
                }
            }

            if ($this->filter_class) {
                $studentsQuery->whereHas('enrollments', function ($q) use ($sessionId, $shiftType) {
                    $q->where('academic_session_id', $sessionId)
                      ->where('shift_type', $shiftType)
                      ->where('class_id', $this->filter_class);
                });
            }

            if (!empty(trim($this->search))) {
                $studentsQuery->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('admission_no', 'like', '%' . $this->search . '%')
                      ->orWhereHas('enrollments', function($eq) {
                          $eq->where('roll_number', 'like', '%' . $this->search . '%')
                             ->where('academic_session_id', \App\Models\AcademicSession::getActiveSessionId());
                      });
                });
            }

            $students = $studentsQuery->take(15)->get();

            foreach ($students as $student) {
                $student->total_due = \App\Models\FeeRecord::where('student_id', $student->id)
                    ->where('status', '!=', 'paid')
                    ->where('period', '<=', now()->format('Y-m'))
                    ->sum('balance');
            }
        }

        $layout = request()->is('teacher/*') 
            ? 'components.layouts.teacher' 
            : 'components.layouts.admin';

        return view('livewire.admin.fee.record-payment', [
            'studentsList' => $students,
            'classes' => \App\Models\Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $sessionId)
                ->where('shift_type', $shiftType)
                ->orderBy('numeric_value')
                ->get()
        ])->layout($layout, ['title' => 'Collect Fees']);
    }

    public function selectStudent($studentId)
    {
        $this->selectedStudentId = $studentId;
        $this->selectedStudent = \App\Models\Student::withTrashed()->with('class')->findOrFail($studentId);
        $this->unpaidRecords = \App\Models\FeeRecord::where('student_id', $studentId)
            ->where('status', '!=', 'paid')
            ->orderBy('period', 'asc')
            ->get();
    }

    public function resetStudentSelection()
    {
        $this->selectedStudentId = null;
        $this->selectedStudent = null;
        $this->unpaidRecords = [];
        $this->search = '';
    }

    public function selectRecord($recordId)
    {
        $this->loadRecord($recordId);
    }

    #[On('openPaymentModal')]
    public function loadRecord($recordId)
    {
        $this->recordId = $recordId;
        $this->record = FeeRecord::with(['student', 'items'])->findOrFail($recordId);
        
        $this->payment_date = date('Y-m-d');
        $this->payment_method = 'cash';
        $this->reference_number = '';
        $this->remarks = '';
        
        $this->itemPayments = [];
        foreach ($this->record->items as $item) {
            if ($item->amount <= 0) {
                continue;
            }
            $balance = $item->balance !== null ? (float)$item->balance : (float)$item->amount;
            $this->itemPayments[$item->id] = $balance;
        }

        $this->calculateTotalAmount();
        $this->isOpen = true;
    }

    public function updatedItemPayments($value, $key)
    {
        $this->calculateTotalAmount();
    }

    public function updatedAmount($value)
    {
        if (!$this->recordId) {
            return;
        }

        // Re-fetch items fresh — Livewire re-hydration drops eager-loaded relations from JSON
        $record = FeeRecord::with('items')->find($this->recordId);
        if (!$record) {
            return;
        }

        $value = (float)$value;
        $remaining = $value;
        
        foreach ($record->items as $item) {
            if ($item->amount <= 0) {
                continue;
            }
            
            $maxBalance = $item->balance !== null ? (float)$item->balance : (float)$item->amount;
            $itemPay = min($remaining, $maxBalance);
            $this->itemPayments[$item->id] = $itemPay;
            $remaining -= $itemPay;
        }
    }

    public function calculateTotalAmount()
    {
        $sum = 0;
        foreach ($this->itemPayments as $itemId => $payAmount) {
            $sum += (float)$payAmount;
        }
        $this->amount = $sum;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->record = null;
        $this->recordId = null;
        $this->itemPayments = [];
        $this->resetValidation();
    }

    public function storePayment(\App\Services\FeePaymentService $feeService)
    {
        $this->validate([
            'amount' => 'required|numeric|min:1|max:' . $this->record->balance,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        // Validate individual item payments
        foreach ($this->record->items as $item) {
            if ($item->amount <= 0) {
                continue;
            }
            $itemId = $item->id;
            $payAmount = isset($this->itemPayments[$itemId]) ? (float)$this->itemPayments[$itemId] : 0.0;
            $maxBalance = $item->balance !== null ? (float)$item->balance : (float)$item->amount;
            
            if ($payAmount < 0 || $payAmount > $maxBalance) {
                $this->addError('itemPayments.' . $itemId, 'Invalid payment amount.');
                return;
            }
        }

        try {
            $payment = $feeService->recordPayment(
                record: $this->record,
                amount: (float) $this->amount,
                paymentMethod: $this->payment_method,
                referenceNumber: $this->reference_number,
                remarks: $this->remarks,
                receivedByUserId: auth()->id(),
                paymentDate: $this->payment_date,
                itemPayments: $this->itemPayments
            );

            $this->lastReceipt = $payment;
            $this->showReceiptModal = true;
            $this->resetStudentSelection();

            session()->flash('message', "Payment recorded successfully! Receipt #{$payment->receipt_number}");
            $this->close();
            
            // Refresh parent
            $this->dispatch('refreshFeeRecords');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->lastReceipt = null;
    }
}
