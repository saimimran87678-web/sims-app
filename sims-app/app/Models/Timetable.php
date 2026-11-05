<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Timetable extends Model
{
    use LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        $className = $this->class?->name ?? "Class #{$this->class_id}";
        $subjectName = $this->subject?->name ?? "Subject #{$this->subject_id}";
        $day = $this->day ? ucfirst($this->day) : 'Universal';
        return "Timetable: {$className} - {$subjectName} (Period {$this->period_no}, {$day})";
    }
    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'day',
        'period_no',
        'room',
        'is_divided',
        'is_merged',
        'merge_group_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'is_divided' => 'boolean',
        'is_merged' => 'boolean',
        'period_no' => 'integer',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
