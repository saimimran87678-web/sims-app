<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarksConfig extends Model
{
    protected $fillable = [
        'exam_id',
        'class_id',
        'subject',
        'total_marks',
        'passing_marks',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Get the actual passing score (not percentage)
     * Example: total_marks=75, passing_marks=33 => returns 24.75
     */
    public function getPassingScore(): float
    {
        return ($this->total_marks * $this->passing_marks) / 100;
    }
}
