<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PeriodConfig extends Model
{
    use LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        $label = $this->label ?: ($this->is_assembly ? 'Assembly' : ($this->is_break ? 'Break' : "Period {$this->period_no}"));
        return "Bell Period: {$label} ({$this->start_time} - {$this->end_time})";
    }
    protected $fillable = [
        'period_no',
        'start_time',
        'end_time',
        'is_break',
        'is_assembly',
        'label',
        'shift_type',
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'is_assembly' => 'boolean',
    ];
}
