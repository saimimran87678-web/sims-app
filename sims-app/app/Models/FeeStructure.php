<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $fillable = [
        'class_id',
        'fee_head_id',
        'academic_session_id',
        'subject_id',
        'amount',
        'cycle',
        'custom_due_date',
        'effective_from',
        'effective_to',
        'is_active',
        'shift_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'custom_due_date' => 'date',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function feeHead()
    {
        return $this->belongsTo(FeeHead::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
