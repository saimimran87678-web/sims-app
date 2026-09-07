<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
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
