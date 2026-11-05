<?php

namespace App\Services;

use App\Models\FeeRecord;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FeePaymentService
{
    /**
     * Record a payment against a FeeRecord atomically.
     */
    public function recordPayment(
        FeeRecord $record,
        float $amount,
        string $paymentMethod = 'cash',
        ?string $referenceNumber = null,
        ?string $remarks = null,
        ?int $receivedByUserId = null,
        ?string $paymentDate = null,
        array $itemPayments = []
    ): FeePayment {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if ($amount > (float) $record->balance) {
            throw new \InvalidArgumentException('Payment amount cannot exceed the remaining balance of Rs. ' . number_format($record->balance, 2));
        }

        $paymentDate = $paymentDate ?? date('Y-m-d');
        $receivedByUserId = $receivedByUserId ?? auth()->id();
        $receivedByName = $receivedByUserId ? (User::find($receivedByUserId)?->name ?? 'Staff') : 'System';

        $notes = $remarks ?? '';
        if ($referenceNumber) {
            $notes = trim(($notes ? $notes . ' ' : '') . '[Ref: ' . $referenceNumber . ']');
        }

        return DB::transaction(function () use (
            $record,
            $amount,
            $paymentMethod,
            $receivedByName,
            $receivedByUserId,
            $paymentDate,
            $notes,
            $itemPayments
        ) {
            $receiptNumber = FeePayment::generateNextReceiptNumber();

            $payment = FeePayment::create([
                'fee_record_id' => $record->id,
                'student_id' => $record->student_id,
                'receipt_number' => $receiptNumber,
                'amount_paid' => $amount,
                'payment_method' => $paymentMethod,
                'received_by' => $receivedByName,
                'received_by_user_id' => $receivedByUserId,
                'payment_date' => $paymentDate,
                'notes' => $notes,
                'is_void' => false,
            ]);

            // Single Source of Truth balance recalculation
            $record->recalculateBalances();

            // Activity Log
            ActivityLogger::log(
                action: 'created',
                module: 'fee',
                description: "Recorded Fee Payment {$receiptNumber} of Rs. " . number_format($amount, 0) . " for Student #{$record->student_id}",
                subjectLabel: "Receipt {$receiptNumber}",
                newValues: [
                    'receipt_number' => $receiptNumber,
                    'amount_paid' => $amount,
                    'payment_method' => $paymentMethod,
                    'fee_record_id' => $record->id,
                    'student_id' => $record->student_id,
                ]
            );

            // Attempt to dispatch WhatsApp Notification
            try {
                $whatsapp = app(WhatsAppService::class);
                $whatsapp->sendPaymentNotification($payment);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp payment notification failed: ' . $e->getMessage());
            }

            return $payment;
        });
    }

    /**
     * Check if institutional fee void policy is enabled.
     */
    public function isVoidPolicyEnabled(): bool
    {
        $val = \App\Models\Setting::getGlobal('fee_void_enabled', '1');
        return $val !== '0' && $val !== false && $val !== 0;
    }

    /**
     * Check if cashiers are allowed to submit void requests for supervisor approval.
     */
    public function isVoidRequestsAllowed(): bool
    {
        $val = \App\Models\Setting::getGlobal('fee_void_allow_requests', '1');
        return $val !== '0' && $val !== false && $val !== 0;
    }

    /**
     * Check if supervisor password/PIN is strictly required to authorize voids.
     */
    public function isSupervisorPinRequired(): bool
    {
        $val = \App\Models\Setting::getGlobal('fee_void_require_pin', '0');
        return $val === '1' || $val === true || $val === 1;
    }

    /**
     * Update institutional void policy configuration.
     */
    public function updateVoidPolicy(bool $enabled, bool $allowRequests = true, bool $requirePin = true): void
    {
        \App\Models\Setting::setGlobal('fee_void_enabled', $enabled ? '1' : '0');
        \App\Models\Setting::setGlobal('fee_void_allow_requests', $allowRequests ? '1' : '0');
        \App\Models\Setting::setGlobal('fee_void_require_pin', $requirePin ? '1' : '0');

        \App\Services\ActivityLogger::log(
            action: 'updated',
            module: 'fee',
            description: "Updated Fee Void Institutional Policy: Enabled=" . ($enabled ? 'Yes' : 'No') . ", AllowRequests=" . ($allowRequests ? 'Yes' : 'No') . ", RequirePIN=" . ($requirePin ? 'Yes' : 'No'),
            subjectLabel: 'Fee Void Policy'
        );
    }

    /**
     * Submit a void request from a cashier/clerk for supervisor authorization.
     */
    public function requestPaymentVoid(int $paymentId, string $reason, int $requesterUserId): bool
    {
        if (!$this->isVoidPolicyEnabled()) {
            throw new \DomainException('Fee payment voiding is currently disabled by institutional policy.');
        }

        if (!$this->isVoidRequestsAllowed()) {
            throw new \DomainException('Cashier void requests are disabled by institutional policy. Supervisor must authorize on-site.');
        }

        $payment = FeePayment::findOrFail($paymentId);

        if ($payment->is_void) {
            throw new \DomainException('This receipt has already been voided.');
        }

        if ($payment->void_status === 'pending') {
            throw new \DomainException('A void request for this receipt is already pending authorization.');
        }

        $payment->requestVoid($reason, $requesterUserId);
        return true;
    }

    /**
     * Authorize and execute a payment void with Superadmin/Principal verification.
     */
    public function voidPayment(
        int $paymentId,
        string $reason,
        int $authorizerUserId,
        ?string $authorizerPassword = null
    ): bool {
        if (!$this->isVoidPolicyEnabled()) {
            throw new \DomainException('Fee payment voiding is currently disabled by institutional policy.');
        }

        $payment = FeePayment::with('record')->findOrFail($paymentId);

        if ($payment->is_void) {
            throw new \DomainException('This receipt has already been voided.');
        }

        $authorizer = User::findOrFail($authorizerUserId);

        // Security check: Must have fees.void permission OR Super Admin role OR user ID 1
        $isSuperAdmin = $authorizer->hasRole('Super Admin') || $authorizer->id === 1 || $authorizer->can('fees.void');
        if (!$isSuperAdmin) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not have permission to authorize payment voids.');
        }

        // Verify password if required
        if ($authorizerPassword !== null) {
            if (!Hash::check($authorizerPassword, $authorizer->password)) {
                throw new \InvalidArgumentException('Invalid supervisor password provided.');
            }
        } elseif ($this->isSupervisorPinRequired()) {
            throw new \InvalidArgumentException('Supervisor password is required by institutional policy.');
        }

        $payment->voidPayment($reason, $authorizerUserId);

        return true;
    }

    /**
     * Authorize a pending void request.
     */
    public function authorizePendingVoid(int $paymentId, int $authorizerUserId, ?string $authorizerPassword = null): bool
    {
        $payment = FeePayment::findOrFail($paymentId);

        if ($payment->void_status !== 'pending' && !$payment->is_void) {
            // Direct void if not pending
        }

        return $this->voidPayment(
            paymentId: $paymentId,
            reason: $payment->void_reason ?? 'Approved by supervisor',
            authorizerUserId: $authorizerUserId,
            authorizerPassword: $authorizerPassword
        );
    }

    /**
     * Reject a pending void request.
     */
    public function rejectPendingVoid(int $paymentId, string $rejectionReason, int $rejectorUserId): bool
    {
        $payment = FeePayment::findOrFail($paymentId);

        if ($payment->is_void) {
            throw new \DomainException('Cannot reject a request on an already voided payment.');
        }

        $rejector = User::findOrFail($rejectorUserId);
        $isAuthorized = $rejector->hasRole('Super Admin') || $rejector->id === 1 || $rejector->can('fees.void');
        if (!$isAuthorized) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not have permission to reject payment void requests.');
        }

        $payment->rejectVoid($rejectionReason, $rejectorUserId);
        return true;
    }

    /**
     * Retrieve all pending void requests across the institution.
     */
    public function getPendingVoidRequests(): \Illuminate\Support\Collection
    {
        return FeePayment::with(['student', 'record.class', 'receivedByUser', 'voidRequestedByUser'])
            ->where('void_status', 'pending')
            ->where('is_void', false)
            ->orderBy('void_requested_at', 'desc')
            ->get();
    }

    /**
     * Generate an accounting running-balance ledger for a given student.
     */
    public function getStudentLedger(int $studentId): array
    {
        $student = Student::withTrashed()->with('class')->findOrFail($studentId);

        $records = FeeRecord::with(['items', 'payments.receivedByUser', 'payments.voidedByUser', 'payments.voidRequestedByUser'])
            ->where('student_id', $studentId)
            ->orderBy('period', 'asc')
            ->get();

        // 1. Build unified timeline of transactions (Invoices and Payments)
        $timeline = [];

        foreach ($records as $record) {
            // Debit: Fee Voucher Generated
            $periodFormatted = date('M Y', strtotime($record->period . '-01'));
            $timeline[] = [
                'type' => 'invoice',
                'date' => $record->created_at ?? date('Y-m-d H:i:s', strtotime($record->period . '-01')),
                'period' => $record->period,
                'period_label' => $periodFormatted,
                'title' => "Fee Voucher - {$periodFormatted}",
                'ref_no' => "VCH-{$record->id}",
                'debit' => (float) $record->total_amount,
                'credit' => 0.0,
                'is_void' => false,
                'status' => $record->status,
                'record' => $record,
                'payment' => null,
            ];

            // Credits: Payments recorded
            foreach ($record->payments as $payment) {
                $isVoid = (bool) $payment->is_void;
                $voidStatus = $payment->void_status ?? ($isVoid ? 'approved' : 'none');
                
                $statusLabel = 'cleared';
                if ($isVoid) {
                    $statusLabel = 'voided';
                } elseif ($voidStatus === 'pending') {
                    $statusLabel = 'pending_void';
                } elseif ($voidStatus === 'rejected') {
                    $statusLabel = 'void_rejected';
                }

                $timeline[] = [
                    'type' => 'payment',
                    'date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') . ' ' . $payment->created_at->format('H:i:s') : $payment->created_at,
                    'period' => $record->period,
                    'period_label' => $periodFormatted,
                    'title' => $isVoid ? "Voided Payment ({$payment->receipt_number})" : "Fee Payment ({$payment->receipt_number})",
                    'ref_no' => $payment->receipt_number,
                    'debit' => 0.0,
                    'credit' => (float) $payment->amount_paid,
                    'is_void' => $isVoid,
                    'void_status' => $voidStatus,
                    'void_reason' => $payment->void_reason,
                    'voided_at' => $payment->voided_at,
                    'voided_by' => $payment->voidedByUser?->name,
                    'void_requested_at' => $payment->void_requested_at,
                    'void_requested_by' => $payment->voidRequestedByUser?->name,
                    'void_rejection_reason' => $payment->void_rejection_reason,
                    'status' => $statusLabel,
                    'record' => $record,
                    'payment' => $payment,
                ];
            }
        }

        // Sort chronologically
        usort($timeline, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // Compute Running Balance
        $runningBalance = 0.0;
        $totalBilled = 0.0;
        $totalPaid = 0.0;
        $totalVoided = 0.0;

        foreach ($timeline as &$tx) {
            if ($tx['type'] === 'invoice') {
                $runningBalance += $tx['debit'];
                $totalBilled += $tx['debit'];
            } elseif ($tx['type'] === 'payment') {
                if (!$tx['is_void']) {
                    $runningBalance -= $tx['credit'];
                    $totalPaid += $tx['credit'];
                } else {
                    $totalVoided += $tx['credit'];
                }
            }
            $tx['running_balance'] = max(0, $runningBalance);
        }
        unset($tx);

        // Sort descending for display (most recent first)
        $timelineDesc = array_reverse($timeline);

        return [
            'student' => $student,
            'summary' => [
                'totalBilled' => $totalBilled,
                'totalPaid' => $totalPaid,
                'totalVoided' => $totalVoided,
                'currentBalance' => max(0, $totalBilled - $totalPaid),
                'activeVouchers' => $records->where('status', '!=', 'paid')->count(),
            ],
            'timeline' => $timelineDesc,
            'records' => $records,
        ];
    }

    /**
     * Day-End Cashier Closing Reconciliation.
     */
    public function getDailyCollectionSummary(?string $date = null, ?int $cashierUserId = null): array
    {
        $date = $date ?? date('Y-m-d');

        $query = FeePayment::with(['student', 'receivedByUser', 'voidedByUser'])
            ->whereDate('payment_date', $date);

        if ($cashierUserId) {
            $query->where('received_by_user_id', $cashierUserId);
        }

        $allPayments = $query->get();

        $validPayments = $allPayments->where('is_void', false);
        $voidedPayments = $allPayments->where('is_void', true);

        $totalCollected = (float) $validPayments->sum('amount_paid');
        $cashCollected = (float) $validPayments->where('payment_method', 'cash')->sum('amount_paid');
        $bankCollected = (float) $validPayments->whereIn('payment_method', ['bank_transfer', 'online', 'cheque'])->sum('amount_paid');
        $voidedTotal = (float) $voidedPayments->sum('amount_paid');

        $byCashier = $validPayments->groupBy('received_by')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => (float) $group->sum('amount_paid'),
            ];
        })->toArray();

        return [
            'date' => $date,
            'totalCollected' => $totalCollected,
            'cashCollected' => $cashCollected,
            'bankCollected' => $bankCollected,
            'voidedTotal' => $voidedTotal,
            'validCount' => $validPayments->count(),
            'voidedCount' => $voidedPayments->count(),
            'byCashier' => $byCashier,
            'payments' => $allPayments->sortByDesc('id')->values(),
        ];
    }
}
