<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\LogsActivity;

class Classes extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        return "Class: {$this->name}";
    }

    protected $table = 'classes';
    
    protected $fillable = ['name', 'numeric_value', 'academic_session_id', 'next_class_id', 'shift_type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'numeric_value' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($class) {
            if (empty($class->shift_type)) {
                $session = \App\Models\AcademicSession::find($class->academic_session_id);
                if ($session) {
                    $class->shift_type = ($session->shift_type === 'Regular') ? 'regular' : 'morning';
                } else {
                    $class->shift_type = 'regular';
                }
            }

            if (!isset($class->is_active)) {
                $class->is_active = true;
            }
        });

        static::deleting(function ($class) {
            if ($class->isForceDeleting()) {
                $hasEnrollments = \Illuminate\Support\Facades\DB::table('enrollments')->where('class_id', $class->id)->exists();
                $hasFees = \Illuminate\Support\Facades\DB::table('fee_records')->where('class_id', $class->id)->exists();
                $hasMarks = \Illuminate\Support\Facades\DB::table('marks_configs')->where('class_id', $class->id)->exists();
                if ($hasEnrollments || $hasFees || $hasMarks) {
                    throw new \DomainException("Class '{$class->name}' cannot be permanently deleted because historical student, fee, or exam records exist. Archive the class instead to preserve historical records.");
                }
            }
        });

        static::updating(function ($class) {
            if ($class->isDirty('numeric_value')) {
                $hasEnrollments = \Illuminate\Support\Facades\DB::table('enrollments')->where('class_id', $class->id)->exists();
                if ($hasEnrollments) {
                    \Illuminate\Support\Facades\Log::warning("Grade level numeric_value modified for class '{$class->name}' (ID: {$class->id}) with existing student enrollments.");
                }
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForShift($query, $shift)
    {
        if ($shift && $shift !== 'both') {
            return $query->where('shift_type', $shift);
        }
        return $query;
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_id');
    }

    public function getOfferedSubjectsAttribute()
    {
        return Subject::forClass($this->id)->get();
    }

    public function getSubjectsCountAttribute()
    {
        if (array_key_exists('subjects_count', $this->attributes)) {
            $count = (int) $this->attributes['subjects_count'];
            if ($count > 0) {
                return $count;
            }
        }

        return Subject::forClass($this->id)->count();
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'class_programs', 'class_id', 'program_id')
            ->using(ClassProgram::class)
            ->withPivot('is_default')
            ->withTimestamps();
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
