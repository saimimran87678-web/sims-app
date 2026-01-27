<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $fillable = [
        'class_id',
        'subject_id',
        'subject_id_2',
        'teacher_id',
        'schedule_template_id',
        'day',
        'period_no',
        'room',
        'is_divided',
        'is_substitute',
        'substitute_date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'is_divided' => 'boolean',
        'is_substitute' => 'boolean',
        'substitute_date' => 'date',
    ];

    public function template()
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }

    public function class()
    {
        return $this->belongsTo(Classes::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject2()
    {
        return $this->belongsTo(Subject::class, 'subject_id_2');
    }
}
