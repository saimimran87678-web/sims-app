<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class FeeWaiverApplication extends Model
{
    use LogsActivity;

    protected $fillable = [
        'student_id',
        'fee_record_id',
        'waived_amount',
        'concession_type',
        'application_reference',
        'approved_by',
        'reason',
        'attachment_path',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'waived_amount' => 'decimal:2',
    ];

    public function getAmountAttribute()
    {
        return (float) $this->waived_amount;
    }

    public function getActivitySubjectLabel(): string
    {
        return "Fee Waiver: Rs. {$this->waived_amount} (Ref: {$this->application_reference}) for {$this->student?->name}";
    }

    public function student()
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function feeRecord()
    {
        return $this->belongsTo(FeeRecord::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
