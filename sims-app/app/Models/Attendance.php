<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Attendance extends Model
{
    use LogsActivity;

    protected $table = 'attendances';
    protected $fillable = [
        'student_id',
        'academic_session_id',
        'date',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function getActivitySubjectLabel(): string
    {
        $studentName = $this->student?->name ?? "Student #{$this->student_id}";
        return "Student Attendance: {$studentName} ({$this->date})";
    }
}
