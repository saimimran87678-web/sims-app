<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Substitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_attendance_id',
        'timetable_id',
        'substitute_teacher_id',
        'date',
        'status',
    ];

    protected $casts = [
        // 'date' => 'date', // Removed to prevent timezone shifting in SQLite
    ];

    public function teacherAttendance()
    {
        return $this->belongsTo(TeacherAttendance::class);
    }

    public function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }
}
