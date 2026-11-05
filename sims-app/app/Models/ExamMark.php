<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamMark extends Model
{
    protected $table = 'exam_marks';

    protected $fillable = [
        'exam_id',
        'student_id',
        'subject_id',
        'marks_obtained',
        'max_marks',
        'is_absent',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'max_marks' => 'decimal:2',
        'is_absent' => 'boolean',
    ];

    // Relationships
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // Scopes
    public function scopeEntered($query)
    {
        return $query->where('is_absent', false)->whereNotNull('marks_obtained');
    }

    public function scopePending($query)
    {
        return $query->where('is_absent', false)->whereNull('marks_obtained');
    }

    public function scopeAbsent($query)
    {
        return $query->where('is_absent', true);
    }

    // State Helpers
    public function isPending(): bool
    {
        return !$this->is_absent && is_null($this->marks_obtained);
    }

    public function isAbsent(): bool
    {
        return (bool) $this->is_absent;
    }

    public function isScored(): bool
    {
        return !$this->is_absent && !is_null($this->marks_obtained);
    }

    public function getPercentageAttribute(): ?float
    {
        if (!$this->isScored() || empty($this->max_marks) || (float) $this->max_marks <= 0) {
            return null;
        }

        return round(((float) $this->marks_obtained / (float) $this->max_marks) * 100, 2);
    }

    public function isPassed(float $passingPct = 33.0): bool
    {
        if (!$this->isScored()) {
            return false;
        }

        return ($this->percentage ?? 0) >= $passingPct;
    }
}
