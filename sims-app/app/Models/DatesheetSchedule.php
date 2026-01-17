<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatesheetSchedule extends Model
{
    protected $fillable = [
        'exam_id',
        'class_id',
        'exam_date',
        'subject',
        'start_time',
        'end_time',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
