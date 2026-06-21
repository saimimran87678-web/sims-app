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

    public function mount()
    {
        $this->isPage = request()->routeIs('admin.fee.record-payment');
    }

    public function render()
    {
        $students = [];
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();

        if (!empty(trim($this->search)) || $this->filter_class) {
            $studentsQuery = \App\Models\Student::with('class')
                ->whereHas('class', function ($q) use ($sessionId) {
                    $q->where('academic_session_id', $sessionId);
                    if ($this->filter_class) {
                        $q->where('id', $this->filter_class);
                    }
                });

            if ($this->filter_status) {
                $studentsQuery->where('status', $this->filter_status);
            }

            if (!empty(trim($this->search))) {
                $studentsQuery->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('roll_no', 'like', '%' . $this->search . '%')
                      ->orWhere('admission_no', 'like', '%' . $this->search . '%');
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

        return view('livewire.admin.fee.record-payment', [
            'studentsList' => $students,
            'classes' => \App\Models\Classes::where('academic_session_id', $sessionId)->get()
        ])->layout('components.layouts.admin', ['title' => 'Collect Fees']);
    }

    public function selectStudent($studentId)
    {
        $this->selectedStudentId = $studentId;
        $this->selectedStudent = \App\Models\Student::with('class')->findOrFail($studentId);
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
        $value = (float)$value;
        $remaining = $value;
        
        foreach ($this->record->items as $item) {
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

    public function storePayment()
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

        $studentId = $this->record->student_id;
        $payment = null;

        \DB::transaction(function () use (&$payment) {
            $notes = $this->remarks;
            if ($this->reference_number) {
                $notes = trim(($notes ? $notes . ' ' : '') . '[Ref: ' . $this->reference_number . ']');
            }

            // Create payment
            $payment = FeePayment::create([
                'fee_record_id' => $this->recordId,
                'student_id' => $this->record->student_id,
                'amount_paid' => $this->amount,
                'payment_method' => $this->payment_method,
                'received_by' => auth()->user()->name ?? 'System',
                'payment_date' => $this->payment_date,
                'notes' => $notes,
            ]);

            // Update item balances
            foreach ($this->record->items as $item) {
                if ($item->amount <= 0) {
                    continue;
                }
                $itemId = $item->id;
                $payAmount = isset($this->itemPayments[$itemId]) ? (float)$this->itemPayments[$itemId] : 0.0;
                
                $item->paid_amount += $payAmount;
                $item->balance = ($item->balance !== null ? $item->balance : $item->amount) - $payAmount;
                $item->save();
            }

            // Update record balance
            $this->record->paid_amount += $this->amount;
            $this->record->balance -= $this->amount;
            
            if ($this->record->balance <= 0) {
                $this->record->status = 'paid';
            } else {
                $this->record->status = 'partial';
            }
            
            $this->record->save();
        });

        session()->flash('message', 'Payment recorded successfully.');
        $this->close();
        
        // Refresh parent
        $this->dispatch('refreshFeeRecords');

        // Attempt to send WhatsApp Notification
        try {
            if ($payment) {
                $whatsapp = app(\App\Services\WhatsAppService::class);
                $whatsapp->sendPaymentNotification($payment);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to dispatch payment notification: ' . $e->getMessage());
        }

        return redirect()->route('admin.fee.ledger', $studentId);
    }
}
