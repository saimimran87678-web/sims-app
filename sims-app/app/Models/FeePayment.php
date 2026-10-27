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
        'amount_paid',
        'payment_method',
        'received_by',
        'payment_date',
        'notes',
        'whatsapp_sent_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'date',
        'whatsapp_sent_at' => 'datetime',
    ];

    public function record()
    {
        return $this->belongsTo(FeeRecord::class, 'fee_record_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->hasOne(FeeInvoice::class);
    }
}
