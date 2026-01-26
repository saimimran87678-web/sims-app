<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_saturday_working',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_saturday_working' => 'boolean',
    ];

    public function periods()
    {
        return $this->hasMany(PeriodConfig::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }
}
