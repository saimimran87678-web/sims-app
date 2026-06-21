<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_record_id',
        'student_id',
        'invoice_number',
        'invoice_sequence',
        'school_code',
        'period_code',
        'student_name',
        'roll_number',
        'admission_number',
        'parent_phone',
        'class_name',
        'invoice_data',
        'pdf_path',
    ];

    protected $casts = [
        'invoice_data' => 'array',
        'invoice_sequence' => 'integer',
    ];

    public function feeRecord()
    {
        return $this->belongsTo(FeeRecord::class, 'fee_record_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
