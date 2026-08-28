<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classes extends Model
{
    use SoftDeletes;

    protected $table = 'classes';
    
    protected $fillable = ['name', 'numeric_value', 'academic_session_id', 'next_class_id', 'shift_type'];



    protected static function booted()
    {
        static::creating(function ($class) {
            if (empty($class->shift_type)) {
                $session = \App\Models\AcademicSession::find($class->academic_session_id);
                if ($session) {
                    $class->shift_type = ($session->shift_type === 'Regular') ? 'regular' : 'morning';
                } else {
                    $class->shift_type = 'morning';
                }
            }
        });

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
                $user->can('reports.view-sessions') ||
                $user->can('fees.view-sessions') ||
                $user->can('substitutions.view-sessions')
            )) {
                return;
            }

            // Filter by Active Session by default
            $activeId = \App\Models\AcademicSession::getActiveSessionId();
                
            if ($activeId) {
                $builder->where('classes.academic_session_id', $activeId);
                
                // Filter by active shift type
                $sessionObj = \App\Models\AcademicSession::find($activeId);
                $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
                $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
                if ($shiftType === 'both') {
                    $shiftType = 'morning';
                }
                
                $builder->where('classes.shift_type', $shiftType);
            }
        });
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_id');
    }

    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            Enrollment::class,
            'class_id',   // Foreign key on enrollments table...
            'id',         // Foreign key on students table...
            'id',         // Local key on classes table...
            'student_id'  // Local key on enrollments table...
        );
    }

    public function timetables()
    {
        return $this->hasMany(\App\Models\Timetable::class, 'class_id');
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class, 'class_id');
    }

    public function feeRecords()
    {
        return $this->hasMany(FeeRecord::class, 'class_id');
    }

    public function nextClass()
    {
        return $this->belongsTo(self::class, 'next_class_id');
    }
}
