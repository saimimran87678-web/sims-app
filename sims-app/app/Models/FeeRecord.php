<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_session_id',
        'period',
        'cycle',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'due_date',
        'paid_date',
        'is_custom',
        'access_token',
        'access_token_expires_at',
    ];

    protected static function booted()
    {
        static::saving(function ($record) {
            if ($record->academic_session_id && (!app()->runningInConsole() || app()->environment('testing'))) {
                if (AcademicSession::isSessionLocked($record->academic_session_id)) {
                    if (auth()->check() && !auth()->user()->hasRole('Super Admin')) {
                        throw new \DomainException("Academic Session is concluded and locked. Historical fee records cannot be modified.");
                    }
                }
            }
        });

        static::creating(function ($record) {
            if (empty($record->access_token)) {
                $record->access_token = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($record->access_token_expires_at)) {
                $record->access_token_expires_at = now()->addDays(30);
            }
        });
    }

    public function isAccessTokenExpired(): bool
    {
        return $this->access_token_expires_at && now()->greaterThan($this->access_token_expires_at);
    }

    public function regenerateAccessToken(int $daysValid = 30): string
    {
        $newToken = (string) \Illuminate\Support\Str::uuid();
        $this->update([
            'access_token' => $newToken,
            'access_token_expires_at' => now()->addDays($daysValid),
        ]);
        return $newToken;
    }

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'access_token_expires_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function items()
    {
        return $this->hasMany(FeeRecordItem::class)->orderBy('id', 'asc');
    }

    public function payments()
    {
        return $this->hasMany(FeePayment::class);
    }

    /**
     * Single Source of Truth (SSOT) balance recalculation.
     * Computes paid_amount and balance strictly from non-voided payments
     * and synchronizes line-item balances.
     */
    public function recalculateBalances(): void
    {
        $validPayments = $this->payments()->where('is_void', false)->get();
        $totalPaid = (float) $validPayments->sum('amount_paid');

        $this->paid_amount = $totalPaid;
        $this->balance = max(0, (float) $this->total_amount - $totalPaid);
        
        if ($this->balance <= 0) {
            $this->status = 'paid';
            $this->paid_date = $this->paid_date ?? now();
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
            $this->paid_date = null;
        } else {
            $this->status = 'unpaid';
            $this->paid_date = null;
        }

        $this->save();

        // Also synchronize line item allocations
        $remainingPaidToAllocate = $totalPaid;
        foreach ($this->items as $item) {
            $itemAmount = (float) $item->amount;
            if ($itemAmount <= 0) {
                $item->paid_amount = 0;
                $item->balance = 0;
            } else {
                $allocatedToItem = min($remainingPaidToAllocate, $itemAmount);
                $item->paid_amount = $allocatedToItem;
                $item->balance = max(0, $itemAmount - $allocatedToItem);
                $remainingPaidToAllocate -= $allocatedToItem;
            }
            $item->save();
        }
    }
}
