<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_template_id',
        'class_id',
        'section_id',
        'subject_id',
        'subject_id_2', // For double/split subjects
        'teacher_id',
        'day',
        'period_no',
        'start_time',
        'end_time',
        'room',
        'is_divided', // boolean
        'merged_class_id'
    ];

    protected $casts = [
        'is_divided' => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
    
    public function mergedClass()
    {
        return $this->belongsTo(Classes::class, 'merged_class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function subject2()
    {
        return $this->belongsTo(Subject::class, 'subject_id_2');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    public function template()
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }
}
