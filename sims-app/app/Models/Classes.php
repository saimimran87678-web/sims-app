<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classes extends Model
{
    use SoftDeletes;

    protected $table = 'classes';
    
    protected $fillable = ['name', 'numeric_value', 'academic_session_id'];

    protected static function booted()
    {
        static::addGlobalScope('active_session', function ($builder) {
            // Skip restriction if User is Super Admin OR has ANY session view permission
            $user = auth()->user();
            if ($user && (
                $user->hasRole('Super Admin') || 
                $user->can('sessions.view-all') ||
                $user->can('classes.view-sessions') ||
                $user->can('students.view-sessions') ||
                $user->can('exams.view-sessions') ||
                $user->can('schedule.view-sessions') ||
                $user->can('reports.view-sessions')
            )) {
                return;
            }

            // Filter by Active Session by default
            $activeId = \App\Models\AcademicSession::getActiveSessionId();
                
            if ($activeId) {
                $builder->where('academic_session_id', $activeId);
            }
        });
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function timetables()
    {
        return $this->hasMany(\App\Models\Timetable::class, 'class_id');
    }
}
