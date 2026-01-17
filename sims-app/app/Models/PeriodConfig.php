<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodConfig extends Model
{
    protected $fillable = [
        'period_no',
        'start_time',
        'end_time',
        'is_break',
        'is_assembly',
        'label',
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'is_assembly' => 'boolean',
    ];
}
