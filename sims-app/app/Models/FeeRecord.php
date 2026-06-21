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
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
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
        return $this->hasMany(FeeRecordItem::class);
    }

    public function payments()
    {
        return $this->hasMany(FeePayment::class);
    }
}
