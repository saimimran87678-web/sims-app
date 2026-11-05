<?php

namespace App\Livewire\Admin\Fee;

use App\Models\Student;
use App\Models\FeeRecord;
use App\Models\FeePayment;
use App\Models\AcademicSession;
use App\Models\User;
use App\Services\FeePaymentService;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Hash;

class StudentLedger extends Component
{
    public $studentId = null;
    public $student = null;

    // Student Search / Switcher
    public $searchStudent = '';
    public $searchResults = [];

    // Filter: 'all', 'invoices', 'payments', 'voided', 'pending_void'
    public $ledgerFilter = 'all';

    // Void Modal State
    public $showVoidModal = false;
    public $voidPaymentId = null;
    public $voidPaymentReceiptNumber = '';
    public $voidPaymentAmount = 0.0;
    public $voidPaymentDate = '';
    public $voidPaymentStudentName = '';
    public $voidReasonType = 'Entered Wrong Amount';
    public $voidReasonNotes = '';
    public $voidMode = 'request'; // 'request' or 'supervisor'
    public $selectedSupervisorId = null;
    public $supervisorPassword = '';
    public $voidError = '';

    // Approvals Management Modal State (Superadmin / Principal)
    public $showApprovalsModal = false;
    public $pendingRequestsCount = 0;
    public $approvingPaymentId = null;
    public $approvalPassword = '';
    public $approvalError = '';

    // Rejection Modal State
    public $showRejectModal = false;
    public $rejectingPaymentId = null;
    public $rejectionReasonText = '';
    public $rejectionError = '';

    // Institutional Policy Modal State
    public $showPolicyModal = false;
    public $policyVoidEnabled = true;
    public $policyAllowRequests = true;
    public $policyRequirePin = true;

    // Day-End Closing Modal State
    public $showDayEndModal = false;
    public $dayEndDate = '';

    protected $queryString = [
        'studentId' => ['except' => null],
    ];

    #[On('refreshFeeRecords')]
    public function refreshLedger()
    {
        $feeService = app(FeePaymentService::class);
        $this->refreshPendingCount($feeService);
    }

    public function mount($studentId = null)
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && !$user->hasRole('Super Admin')) {
            abort_if(!$user->can('fees.manage'), 403);
        }

        $feeService = app(FeePaymentService::class);
        $this->dayEndDate = date('Y-m-d');
        $this->loadPolicies($feeService);
        $this->refreshPendingCount($feeService);

        if ($studentId) {
            $this->selectStudent($studentId);
        } else {
            // Auto-select first active student with fee records if none specified
            $firstStudentId = FeeRecord::latest()->value('student_id') ?? Student::value('id');
            if ($firstStudentId) {
                $this->selectStudent($firstStudentId);
            }
        }
    }

    public function loadPolicies(FeePaymentService $feeService)
    {
        $this->policyVoidEnabled = $feeService->isVoidPolicyEnabled();
        $this->policyAllowRequests = $feeService->isVoidRequestsAllowed();
        $this->policyRequirePin = $feeService->isSupervisorPinRequired();
    }

    public function refreshPendingCount(FeePaymentService $feeService)
    {
        $this->pendingRequestsCount = FeePayment::where('void_status', 'pending')
            ->where('is_void', false)
            ->count();
    }

    public function updatedSearchStudent()
    {
        $query = trim($this->searchStudent);
        if (strlen($query) < 2) {
            $this->searchResults = [];
            return;
        }

        $sessionId = AcademicSession::getActiveSessionId();

        $this->searchResults = Student::withTrashed()
            ->with('class')
            ->where(function ($q) use ($query, $sessionId) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('admission_no', 'like', "%{$query}%")
                  ->orWhereHas('enrollments', function ($eq) use ($query, $sessionId) {
                      $eq->where('roll_number', 'like', "%{$query}%")
                         ->where('academic_session_id', $sessionId);
                  });
            })
            ->take(8)
            ->get()
            ->toArray();
    }

    public function selectStudent($id)
    {
        $this->studentId = $id;
        $this->student = Student::withTrashed()->with('class')->find($id);
        $this->searchStudent = '';
        $this->searchResults = [];
    }

    public function openVoidModal($paymentId)
    {
        $payment = FeePayment::with(['student', 'receivedByUser'])->findOrFail($paymentId);

        if ($payment->is_void) {
            session()->flash('error', 'This payment is already voided.');
            return;
        }

        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->hasRole('Super Admin') || $currentUser->id === 1 || $currentUser->can('fees.void');

        $this->voidPaymentId = $payment->id;
        $this->voidPaymentReceiptNumber = $payment->receipt_number ?? "REC-{$payment->id}";
        $this->voidPaymentAmount = (float) $payment->amount_paid;
        $this->voidPaymentDate = $payment->payment_date ? $payment->payment_date->format('d M, Y') : 'N/A';
        $this->voidPaymentStudentName = $payment->student?->name ?? 'Student';
        $this->voidReasonType = 'Entered Wrong Amount';
        $this->voidReasonNotes = '';
        $this->voidMode = $isSuperAdmin ? 'supervisor' : ($this->policyAllowRequests ? 'request' : 'supervisor');
        
        $superAdmin = User::role('Super Admin')->first() ?? User::find(1);
        $this->selectedSupervisorId = $superAdmin?->id;
        $this->supervisorPassword = '';
        $this->voidError = '';
        $this->showVoidModal = true;
    }

    public function closeVoidModal()
    {
        $this->showVoidModal = false;
        $this->voidPaymentId = null;
        $this->voidError = '';
    }

    public function confirmVoid(FeePaymentService $feeService)
    {
        $this->voidError = '';

        if (!$this->policyVoidEnabled) {
            $this->voidError = 'Fee payment voiding is disabled by institutional policy.';
            return;
        }

        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->hasRole('Super Admin') || $currentUser->id === 1 || $currentUser->can('fees.void');

        $fullReason = $this->voidReasonType;
        if (!empty(trim($this->voidReasonNotes))) {
            $fullReason .= ': ' . trim($this->voidReasonNotes);
        }

        // Branch 1: Submit as asynchronous void request (Maker-Checker Queue)
        if (!$isSuperAdmin && $this->voidMode === 'request') {
            try {
                $feeService->requestPaymentVoid(
                    paymentId: $this->voidPaymentId,
                    reason: $fullReason,
                    requesterUserId: $currentUser->id
                );

                session()->flash('message', "Void request for Receipt {$this->voidPaymentReceiptNumber} submitted for Principal / Super Admin authorization.");
                $this->closeVoidModal();
                $this->refreshPendingCount($feeService);
                $this->dispatch('refreshFeeRecords');
                return;
            } catch (\Throwable $e) {
                $this->voidError = $e->getMessage();
                return;
            }
        }

        // Branch 2: Immediate Supervisor Sign-off
        $authorizerId = null;
        if (!$isSuperAdmin) {
            if (empty($this->supervisorPassword)) {
                $this->voidError = 'Supervisor / Principal password is required to authorize voiding this receipt.';
                return;
            }

            $supervisor = User::find($this->selectedSupervisorId) ?? User::role('Super Admin')->first() ?? User::find(1);
            if (!$supervisor || !Hash::check($this->supervisorPassword, $supervisor->password)) {
                $this->voidError = 'Incorrect supervisor password. Void authorization rejected.';
                return;
            }

            $authorizerId = $supervisor->id;
        } else {
            // Super Admin direct void
            if ($this->policyRequirePin) {
                if (empty($this->supervisorPassword) || !Hash::check($this->supervisorPassword, $currentUser->password)) {
                    $this->voidError = 'Invalid admin password confirmed.';
                    return;
                }
            }
            $authorizerId = $currentUser->id;
        }

        try {
            $feeService->voidPayment(
                paymentId: $this->voidPaymentId,
                reason: $fullReason,
                authorizerUserId: $authorizerId,
                authorizerPassword: $this->supervisorPassword ?: null
            );

            session()->flash('message', "Receipt {$this->voidPaymentReceiptNumber} successfully VOIDED. Balance restored.");
            $this->closeVoidModal();
            $this->refreshPendingCount($feeService);
            $this->dispatch('refreshFeeRecords');
        } catch (\Throwable $e) {
            $this->voidError = $e->getMessage();
        }
    }

    // --- Approvals Queue Modal (Superadmin / Principal) ---
    public function openApprovalsModal()
    {
        $this->approvalError = '';
        $this->approvingPaymentId = null;
        $this->approvalPassword = '';
        $this->showApprovalsModal = true;
    }

    public function closeApprovalsModal()
    {
        $this->showApprovalsModal = false;
        $this->approvingPaymentId = null;
        $this->approvalPassword = '';
        $this->approvalError = '';
    }

    public function promptAuthorizeRequest($paymentId)
    {
        $this->approvingPaymentId = $paymentId;
        $this->approvalPassword = '';
        $this->approvalError = '';
    }

    public function confirmAuthorizeRequest(FeePaymentService $feeService)
    {
        $this->approvalError = '';
        $currentUser = auth()->user();

        if ($this->policyRequirePin) {
            if (empty($this->approvalPassword) || !Hash::check($this->approvalPassword, $currentUser->password)) {
                $this->approvalError = 'Invalid administrator password confirmed.';
                return;
            }
        }

        try {
            $payment = FeePayment::findOrFail($this->approvingPaymentId);
            $feeService->authorizePendingVoid(
                paymentId: $this->approvingPaymentId,
                authorizerUserId: $currentUser->id,
                authorizerPassword: $this->approvalPassword ?: null
            );

            session()->flash('message', "Receipt {$payment->receipt_number} void request APPROVED. Ledger balance restored.");
            $this->approvingPaymentId = null;
            $this->approvalPassword = '';
            $this->refreshPendingCount($feeService);
            $this->dispatch('refreshFeeRecords');
        } catch (\Throwable $e) {
            $this->approvalError = $e->getMessage();
        }
    }

    public function openRejectModal($paymentId)
    {
        $this->rejectingPaymentId = $paymentId;
        $this->rejectionReasonText = '';
        $this->rejectionError = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectingPaymentId = null;
        $this->rejectionReasonText = '';
        $this->rejectionError = '';
    }

    public function confirmRejectRequest(FeePaymentService $feeService)
    {
        $this->rejectionError = '';
        if (empty(trim($this->rejectionReasonText))) {
            $this->rejectionError = 'Please provide a justification for rejecting this void request.';
            return;
        }

        try {
            $payment = FeePayment::findOrFail($this->rejectingPaymentId);
            $feeService->rejectPendingVoid(
                paymentId: $this->rejectingPaymentId,
                rejectionReason: trim($this->rejectionReasonText),
                rejectorUserId: auth()->id()
            );

            session()->flash('message', "Void request for Receipt {$payment->receipt_number} has been REJECTED.");
            $this->closeRejectModal();
            $this->refreshPendingCount($feeService);
            $this->dispatch('refreshFeeRecords');
        } catch (\Throwable $e) {
            $this->rejectionError = $e->getMessage();
        }
    }

    // --- Institutional Policy Modal ---
    public function openPolicyModal()
    {
        $user = auth()->user();
        abort_if(!$user->hasRole('Super Admin') && $user->id !== 1 && !$user->can('fees.void'), 403);

        $feeService = app(FeePaymentService::class);
        $this->loadPolicies($feeService);
        $this->showPolicyModal = true;
    }

    public function closePolicyModal()
    {
        $this->showPolicyModal = false;
    }

    public function savePolicy(FeePaymentService $feeService)
    {
        $user = auth()->user();
        abort_if(!$user->hasRole('Super Admin') && $user->id !== 1 && !$user->can('fees.void'), 403);

        $feeService->updateVoidPolicy(
            enabled: (bool) $this->policyVoidEnabled,
            allowRequests: (bool) $this->policyAllowRequests,
            requirePin: (bool) $this->policyRequirePin
        );

        session()->flash('message', 'Institutional Fee Void Policy updated successfully.');
        $this->closePolicyModal();
    }

    // --- Day-End Closing Modal ---
    public function openDayEndModal()
    {
        $this->dayEndDate = date('Y-m-d');
        $this->showDayEndModal = true;
    }

    public function closeDayEndModal()
    {
        $this->showDayEndModal = false;
    }

    public function render(FeePaymentService $feeService)
    {
        $ledgerData = null;
        if ($this->studentId) {
            $ledgerData = $feeService->getStudentLedger($this->studentId);
        }

        $dayEndSummary = null;
        if ($this->showDayEndModal) {
            $dayEndSummary = $feeService->getDailyCollectionSummary($this->dayEndDate);
        }

        $pendingRequests = [];
        if ($this->showApprovalsModal) {
            $pendingRequests = $feeService->getPendingVoidRequests();
        }

        $supervisors = User::where(function ($q) {
            $q->whereHas('roles', fn($r) => $r->where('name', 'Super Admin'))
              ->orWhere('id', 1)
              ->orWhereHas('permissions', fn($p) => $p->where('name', 'fees.void'));
        })->get();

        $filteredTimeline = [];
        if ($ledgerData) {
            $timeline = $ledgerData['timeline'];
            if ($this->ledgerFilter === 'invoices') {
                $filteredTimeline = array_filter($timeline, fn($t) => $t['type'] === 'invoice');
            } elseif ($this->ledgerFilter === 'payments') {
                $filteredTimeline = array_filter($timeline, fn($t) => $t['type'] === 'payment' && !$t['is_void']);
            } elseif ($this->ledgerFilter === 'voided') {
                $filteredTimeline = array_filter($timeline, fn($t) => !empty($t['is_void']));
            } elseif ($this->ledgerFilter === 'pending_void') {
                $filteredTimeline = array_filter($timeline, fn($t) => ($t['void_status'] ?? '') === 'pending');
            } else {
                $filteredTimeline = $timeline;
            }
        }

        $layout = (request()->is('teacher/*') || (auth()->check() && auth()->user()->role === 'teacher'))
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        return view('livewire.admin.fee.student-ledger', [
            'ledgerData' => $ledgerData,
            'filteredTimeline' => $filteredTimeline,
            'dayEndSummary' => $dayEndSummary,
            'pendingRequests' => $pendingRequests,
            'supervisors' => $supervisors,
        ])->layout($layout, ['title' => 'Student Fee Ledger']);
    }
}

