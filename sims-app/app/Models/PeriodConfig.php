<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodConfig extends Model
{
    protected $fillable = [
        'period_no',
        'schedule_template_id',
        'start_time',
        'end_time',
        'is_break',
        'is_assembly',
        'label',
        'days',
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'is_assembly' => 'boolean',
        'days' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }
}
