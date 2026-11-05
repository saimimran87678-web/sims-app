<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class FeePayment extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        return "Fee Payment: Rs. " . number_format((float)$this->amount_paid, 0) . " (Student #{$this->student_id})";
    }

    protected $fillable = [
        'fee_record_id',
        'student_id',
        'receipt_number',
        'amount_paid',
        'payment_method',
        'received_by',
        'received_by_user_id',
        'payment_date',
        'notes',
        'is_void',
        'void_status',
        'void_requested_at',
        'void_requested_by_user_id',
        'voided_at',
        'voided_by_user_id',
        'void_reason',
        'void_rejection_reason',
        'whatsapp_sent_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'date',
        'is_void' => 'boolean',
        'void_requested_at' => 'datetime',
        'voided_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = static::generateNextReceiptNumber();
            }
        });
    }

    /**
     * Generate formatted sequential receipt number (e.g. REC-2026-00001).
     */
    public static function generateNextReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = "REC-{$year}-";

        $lastNumber = \Illuminate\Support\Facades\DB::table('fee_payments')
            ->where('receipt_number', 'like', "{$prefix}%")
            ->orderBy('receipt_number', 'desc')
            ->value('receipt_number');

        if ($lastNumber) {
            $seq = (int) substr($lastNumber, strlen($prefix));
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf("%s%05d", $prefix, $nextSeq);
    }

    public function record()
    {
        return $this->belongsTo(FeeRecord::class, 'fee_record_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function voidRequestedByUser()
    {
        return $this->belongsTo(User::class, 'void_requested_by_user_id');
    }

    public function invoice()
    {
        return $this->hasOne(FeeInvoice::class);
    }

    /**
     * Submit a payment void request for supervisor / principal authorization.
     */
    public function requestVoid(string $reason, int $requesterUserId): void
    {
        $this->update([
            'void_status' => 'pending',
            'void_requested_at' => now(),
            'void_requested_by_user_id' => $requesterUserId,
            'void_reason' => $reason,
        ]);

        \App\Services\ActivityLogger::log(
            action: 'updated',
            module: 'fee',
            description: "Submitted void request for Fee Receipt {$this->receipt_number} (Rs. " . number_format((float)$this->amount_paid, 0) . "). Reason: {$reason}",
            subjectLabel: "Receipt {$this->receipt_number}",
            newValues: [
                'receipt_number' => $this->receipt_number,
                'void_status' => 'pending',
                'void_reason' => $reason,
                'void_requested_by_user_id' => $requesterUserId,
            ]
        );
    }

    /**
     * Reject a pending payment void request.
     */
    public function rejectVoid(string $rejectionReason, int $rejectorUserId): void
    {
        $this->update([
            'void_status' => 'rejected',
            'void_rejection_reason' => $rejectionReason,
        ]);

        \App\Services\ActivityLogger::log(
            action: 'updated',
            module: 'fee',
            description: "Rejected void request for Fee Receipt {$this->receipt_number}. Reason: {$rejectionReason}",
            subjectLabel: "Receipt {$this->receipt_number}",
            newValues: [
                'receipt_number' => $this->receipt_number,
                'void_status' => 'rejected',
                'void_rejection_reason' => $rejectionReason,
                'rejected_by_user_id' => $rejectorUserId,
            ]
        );
    }

    /**
     * Atomically void the payment, recalculate bill balances, and log activity.
     */
    public function voidPayment(string $reason, int $authorizingUserId): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($reason, $authorizingUserId) {
            $this->update([
                'is_void' => true,
                'void_status' => 'approved',
                'voided_at' => now(),
                'voided_by_user_id' => $authorizingUserId,
                'void_reason' => $reason,
            ]);

            if ($this->record) {
                $this->record->recalculateBalances();
            }

            \App\Services\ActivityLogger::log(
                action: 'voided',
                module: 'fee',
                description: "Voided Fee Receipt {$this->receipt_number} for Rs. " . number_format((float)$this->amount_paid, 0) . ". Reason: {$reason}",
                subjectLabel: "Receipt {$this->receipt_number}",
                newValues: [
                    'receipt_number' => $this->receipt_number,
                    'amount_paid' => $this->amount_paid,
                    'void_status' => 'approved',
                    'void_reason' => $reason,
                    'voided_by_user_id' => $authorizingUserId,
                ]
            );
        });
    }
}
