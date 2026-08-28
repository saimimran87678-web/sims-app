<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_session_id',
        'program_id',
        'roll_number',
        'shift_type',
        'status',
        'promoted_to_class_id',
        'promoted_at',
        'remarks',
    ];

    protected $casts = [
        'promoted_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function currentClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function promotedToClass()
    {
        return $this->belongsTo(Classes::class, 'promoted_to_class_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'student_id'); // Temporary mapping until Step 3 adjusts column to enrollment_id
    }

    public function feeInvoices()
    {
        return $this->hasMany(FeeInvoice::class, 'student_id', 'student_id'); // Temporary mapping until Step 3 adjusts column to enrollment_id
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSession($query, $id)
    {
        return $query->where('academic_session_id', $id);
    }

    public function scopeForClass($query, $id)
    {
        return $query->where('class_id', $id);
    }

    public function scopeForShift($query, $shift)
    {
        return $query->where('shift_type', strtolower($shift));
    }

    public function scopeMorning($query)
    {
        return $query->where('shift_type', 'morning');
    }

    public function scopeEvening($query)
    {
        return $query->where('shift_type', 'evening');
    }

    public function isDualShift(): bool
    {
        return self::where('student_id', $this->student_id)
            ->where('academic_session_id', $this->academic_session_id)
            ->where('shift_type', '!=', $this->shift_type)
            ->exists();
    }
}
