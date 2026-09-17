<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherDuty extends Model
{
    protected $guarded = [];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function scheduleTemplate()
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }
}
