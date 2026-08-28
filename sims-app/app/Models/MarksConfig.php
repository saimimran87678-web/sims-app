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
        'academic_session_id',
        'subject_id',
        'program_id',
        'theory_marks',
        'practical_marks',
        'is_board_exam',
        'effective_from',
    ];

    protected $casts = [
        'is_board_exam' => 'boolean',
        'effective_from' => 'date',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subjectModel()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function getPassingScore(): float
    {
        return ($this->total_marks * $this->passing_marks) / 100;
    }

    public static function effectiveFor($sessionId, $examId, $subjectId, $date)
    {
        return static::where('academic_session_id', $sessionId)
            ->where('exam_id', $examId)
            ->where('subject_id', $subjectId)
            ->where('effective_from', '<=', $date)
            ->orderBy('effective_from', 'desc')
            ->first();
    }
}
