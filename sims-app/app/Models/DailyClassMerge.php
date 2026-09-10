<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyClassMerge extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'source_timetable_id',
        'target_timetable_id',
    ];

    public function sourceTimetable()
    {
        return $this->belongsTo(Timetable::class, 'source_timetable_id');
    }

    public function targetTimetable()
    {
        return $this->belongsTo(Timetable::class, 'target_timetable_id');
    }
}
