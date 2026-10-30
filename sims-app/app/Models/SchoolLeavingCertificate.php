<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SchoolLeavingCertificate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'student_id',
        'academic_session_id',
        'enrollment_id',
        'certificate_number',
        'leaving_date',
        'class_at_leaving',
        'reason_for_leaving',
        'conduct',
        'remarks',
        'fee_dues_cleared',
        'concession_reference',
        'issued_by_user_id',
    ];

    protected $casts = [
        'leaving_date' => 'date',
        'fee_dues_cleared' => 'boolean',
    ];

    public function getReasonAttribute()
    {
        return $this->attributes['reason_for_leaving'] ?? null;
    }

    public function setReasonAttribute($value)
    {
        $this->attributes['reason_for_leaving'] = $value;
    }

    public function getFinancialClearanceVerifiedAttribute()
    {
        return $this->attributes['fee_dues_cleared'] ?? true;
    }

    public function setFinancialClearanceVerifiedAttribute($value)
    {
        $this->attributes['fee_dues_cleared'] = $value;
    }

    public function getIssuedByAttribute()
    {
        return $this->attributes['issued_by_user_id'] ?? null;
    }

    public function setIssuedByAttribute($value)
    {
        $this->attributes['issued_by_user_id'] = $value;
    }

    public function getIssueDateAttribute()
    {
        return $this->created_at ?? now();
    }

    public function getActivitySubjectLabel(): string
    {
        return "SLC #{$this->certificate_number} for {$this->student?->name}";
    }

    public function student()
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
