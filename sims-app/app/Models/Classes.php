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

    /**
     * Smartly detects the grade level numeric_value from a classroom name.
     * ECE stages:
     *   Playgroup / Daycare / Toddler -> -3
     *   Nursery / Montessori Junior  -> -2
     *   KG-1 / Junior KG / Mont Senior -> -1
     *   KG-2 / Prep / Kindergarten / Senior KG -> 0
     * Standard grades:
     *   Class 1..12, Grade 1..12, 1..12 -> 1..12
     * Cambridge:
     *   O-1 -> 9, O-2 -> 10, O-3 -> 11, A-1 / AS -> 11, A-2 -> 12
     */
    public static function detectNumericValue(string $name): int
    {
        $clean = trim($name);
        $lower = strtolower($clean);

        // 1. Check Early Childhood Education / Montessori keywords
        if (preg_match('/\b(playgroup|toddler|daycare|infant)\b/i', $lower)) {
            return -3;
        }

        if (preg_match('/\b(nursery|montessori\s*junior|montessori\s*1)\b/i', $lower)) {
            return -2;
        }

        if (preg_match('/\b(kg[\s\-]*1|junior\s*kg|montessori\s*senior|montessori\s*2)\b/i', $lower)) {
            return -1;
        }

        if (preg_match('/\b(prep|kindergarten|kg[\s\-]*2|senior\s*kg|\bkg\b)\b/i', $lower)) {
            return 0;
        }

        // 2. Cambridge / College streams
        if (preg_match('/\bo[\s\-]*level[\s\-]*1|\bo[\s\-]*1\b/i', $lower)) {
            return 9;
        }
        if (preg_match('/\bo[\s\-]*level[\s\-]*2|\bo[\s\-]*2\b/i', $lower)) {
            return 10;
        }
        if (preg_match('/\bo[\s\-]*level[\s\-]*3|\bo[\s\-]*3\b/i', $lower)) {
            return 11;
        }
        if (preg_match('/\ba[\s\-]*level[\s\-]*1|\bas[\s\-]*level|\ba[\s\-]*1\b/i', $lower)) {
            return 11;
        }
        if (preg_match('/\ba[\s\-]*level[\s\-]*2|\ba2[\s\-]*level|\ba[\s\-]*2\b/i', $lower)) {
            return 12;
        }

        // 3. If hyphen exists, look at first part before hyphen (e.g. "Class 7 - 14 August" or "Class 7 - Iqbal")
        $firstPart = str_contains($clean, '-') ? trim(explode('-', $clean, 2)[0]) : $clean;

        // Check for explicit "Class X" or "Grade X" or standalone digit in first part
        if (preg_match('/\b(?:class|grade)?\s*(\d+)/i', $firstPart, $matches)) {
            return (int) $matches[1];
        }

        // 4. Fallback: extract first integer found in the string
        if (preg_match('/(\d+)/', $clean, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    public function getGradeNameAttribute(): string
    {
        $val = $this->numeric_value;
        if ($val === -3) return 'Playgroup';
        if ($val === -2) return 'Nursery';
        if ($val === -1) return 'KG-1';
        if ($val === 0) {
            if (preg_match('/\b(prep|kindergarten|kg|nursery|playgroup)\b/i', $this->name, $matches)) {
                return ucfirst(strtolower($matches[1]));
            }
            return 'Prep';
        }

        if ($val >= 1 && $val <= 12) {
            return 'Class ' . $val;
        }

        return $this->name;
    }

    public function getSectionNameAttribute(): string
    {
        $name = trim($this->name);

        if (str_contains($name, ' - ')) {
            return trim(explode(' - ', $name, 2)[1]);
        }

        if (str_contains($name, '-')) {
            $parts = explode('-', $name);
            if (count($parts) > 2 && preg_match('/^(?:KG|O|A)$/i', trim($parts[0]))) {
                return trim($parts[2]);
            }
            if (count($parts) > 1 && !preg_match('/^(?:KG|O|A)$/i', trim($parts[0]))) {
                return trim($parts[1]);
            }
        }

        if (preg_match('/^(?:Class\s*)?\d+([A-Za-z]+)$/i', $name, $matches)) {
            return strtoupper($matches[1]);
        }

        $stripped = preg_replace('/^(?:Class\s*\d+|Grade\s*\d+|Playgroup|Nursery|KG[\s\-]*[12]?|Prep|Kindergarten)\s+/i', '', $name);
        return ($stripped !== $name && !empty(trim($stripped))) ? trim($stripped) : '';
    }

    public function getDisplayNameAttribute(): string
    {
        $section = $this->section_name;
        $grade = $this->grade_name;

        if ($section !== '') {
            return "{$grade} — {$section}";
        }

        return $this->name;
    }

    public function getIsEarlyEducationAttribute(): bool
    {
        return $this->numeric_value <= 0;
    }
}
