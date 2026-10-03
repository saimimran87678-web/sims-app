<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DailyClassMerge extends Model
{
    protected $fillable = [
        'academic_session_id',
        'shift_type',
        'date',
        'source_class_id',
        'target_class_id',
        'source_timetable_id',
        'target_timetable_id',
    ];

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function getDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function sourceClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'source_class_id');
    }

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'target_class_id');
    }

    public function sourceTimetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'source_timetable_id');
    }

    public function targetTimetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'target_timetable_id');
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
