<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Substitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_session_id',
        'shift_type',
        'date',
        'period_no',
        'class_id',
        'subject_id',
        'timetable_id',
        'absent_teacher_id',
        'substitute_teacher_id',
        'teacher_attendance_id',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'period_no' => 'integer',
    ];

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function getDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function timetable()
    {
        return $this->belongsTo(Timetable::class, 'timetable_id');
    }

    public function absentTeacher()
    {
        return $this->belongsTo(User::class, 'absent_teacher_id');
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function attendance()
    {
        return $this->belongsTo(TeacherAttendance::class, 'teacher_attendance_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
