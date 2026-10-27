<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class TeacherAttendance extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        $teacherName = $this->teacher?->name ?? "Teacher #{$this->teacher_id}";
        return "Staff Attendance: {$teacherName} ({$this->date})";
    }

    protected $fillable = [
        'teacher_id',
        'date',
        'status',
        'remarks',
        'academic_session_id',
        'shift_type',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function substitutions()
    {
        return $this->hasMany(Substitution::class, 'teacher_attendance_id');
    }
}
