<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeriodConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_template_id',
        'period_no',
        'start_time',
        'end_time',
        'is_break',
        'is_assembly',
        'label',
        'days' // JSON array of applicable days
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'is_assembly' => 'boolean',
        'days' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function template()
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }
}
