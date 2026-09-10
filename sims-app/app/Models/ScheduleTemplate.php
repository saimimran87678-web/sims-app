<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_saturday_working'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_saturday_working' => 'boolean',
    ];

    public function periodConfigs()
    {
        return $this->hasMany(PeriodConfig::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }
}
