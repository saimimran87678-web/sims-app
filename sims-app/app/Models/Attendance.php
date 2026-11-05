<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Attendance extends Model
{
    use LogsActivity;

    protected $table = 'attendances';
    protected $fillable = [
        'enrollment_id',
        'student_id',
        'academic_session_id',
        'date',
        'status',
        'source',
        'check_in_at',
        'check_out_at',
        'device_id',
        'marked_by_user_id',
        'marked_at',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'marked_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($attendance) {
            if (empty($attendance->enrollment_id) && !empty($attendance->student_id)) {
                $enrollment = Enrollment::where('student_id', $attendance->student_id)
                    ->when($attendance->academic_session_id, function ($q, $sessId) {
                        $q->where('academic_session_id', $sessId);
                    })
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderByDesc('id')
                    ->first();
                if ($enrollment) {
                    $attendance->enrollment_id = $enrollment->id;
                    if (empty($attendance->academic_session_id)) {
                        $attendance->academic_session_id = $enrollment->academic_session_id;
                    }
                }
            }
        });
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function scopeRfid($query)
    {
        return $query->where('source', 'rfid');
    }

    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }

    public function scopeToday($query)
    {
        return $query->where('date', now()->format('Y-m-d'));
    }

    public function getActivitySubjectLabel(): string
    {
        $studentName = $this->student?->name ?? "Student #{$this->student_id}";
        return "Student Attendance: {$studentName} ({$this->date})";
    }
}
