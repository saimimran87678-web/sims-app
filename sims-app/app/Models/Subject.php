<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Subject extends Model
{
    use LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        return "Subject: {$this->name}" . ($this->code ? " ({$this->code})" : '');
    }

    protected $fillable = ['class_id', 'name', 'code', 'category', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, $classId)
    {
        if (!$classId) {
            return $query->whereRaw('0 = 1');
        }

        $hasClassPrograms = \Illuminate\Support\Facades\DB::table('class_programs')
            ->where('class_id', $classId)
            ->exists();

        if ($hasClassPrograms) {
            return $query->where(function ($q) use ($classId) {
                $q->whereExists(function ($sub) use ($classId) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('class_programs')
                        ->join('program_subjects', 'class_programs.program_id', '=', 'program_subjects.program_id')
                        ->where('class_programs.class_id', $classId)
                        ->whereColumn('program_subjects.subject_id', 'subjects.id');
                })->orWhere('subjects.class_id', $classId);
            });
        }

        $hasLegacySubjects = \Illuminate\Support\Facades\DB::table('subjects')
            ->where('class_id', $classId)
            ->exists();

        if ($hasLegacySubjects) {
            return $query->where('class_id', $classId);
        }

        return $query->where('is_active', true);
    }

    public function scopeForClasses($query, array $classIds)
    {
        $classIds = array_filter(array_unique($classIds));
        if (empty($classIds)) {
            return $query->whereRaw('0 = 1');
        }

        $hasAnyClassPrograms = \Illuminate\Support\Facades\DB::table('class_programs')
            ->whereIn('class_id', $classIds)
            ->exists();

        if ($hasAnyClassPrograms) {
            return $query->where(function ($q) use ($classIds) {
                $q->whereExists(function ($sub) use ($classIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('class_programs')
                        ->join('program_subjects', 'class_programs.program_id', '=', 'program_subjects.program_id')
                        ->whereIn('class_programs.class_id', $classIds)
                        ->whereColumn('program_subjects.subject_id', 'subjects.id');
                })->orWhereIn('subjects.class_id', $classIds);
            });
        }

        $hasLegacySubjects = \Illuminate\Support\Facades\DB::table('subjects')
            ->whereIn('class_id', $classIds)
            ->exists();

        if ($hasLegacySubjects) {
            return $query->whereIn('class_id', $classIds);
        }

        return $query->where('is_active', true);
    }

    protected $appends = ['abbreviation'];

    public function getAbbreviationAttribute(): string
    {
        return self::formatAbbreviation($this->name, $this->code);
    }

    public static function formatAbbreviation(?string $name, ?string $code = null): string
    {
        if (!empty($code)) {
            return strtoupper(trim($code));
        }
        if (empty($name)) {
            return '';
        }
        $trimmed = trim($name);
        $words = preg_split('/[\s\-_]+/', $trimmed);
        if (count($words) > 1) {
            $initials = '';
            foreach ($words as $w) {
                if (!empty($w)) {
                    $initials .= strtoupper($w[0]);
                }
            }
            if (strlen($initials) >= 2 && strlen($initials) <= 5) {
                return $initials;
            }
        }
        return strtoupper(substr($trimmed, 0, 4));
    }



    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }



    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_subjects')
            ->withPivot('subject_type', 'sort_order')
            ->withTimestamps();
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    /**
     * Inspect all system tables for foreign records linked with this subject.
     */
    public function getLinkedRecordsBreakdown(): array
    {
        $safeCount = function (string $table, string $column = 'subject_id'): int {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return 0;
            }
            return \Illuminate\Support\Facades\DB::table($table)->where($column, $this->id)->count();
        };

        $programs        = $safeCount('program_subjects');
        $allocations     = $safeCount('subject_allocations');
        $examMarks       = $safeCount('exam_marks');
        $marksConfigs    = $safeCount('marks_configs');
        $examSchedules   = $safeCount('exam_schedules');
        $timetables      = $safeCount('timetables');
        $substitutions   = $safeCount('substitutions');
        $studentSubjects = $safeCount('student_subject');
        $gradeLocks      = $safeCount('grade_locks');
        $feeStructures   = $safeCount('fee_structures');

        $total = $programs + $allocations + $examMarks + $marksConfigs + $examSchedules
               + $timetables + $substitutions + $studentSubjects + $gradeLocks + $feeStructures;

        return [
            'total'            => $total,
            'programs'         => $programs,
            'allocations'      => $allocations,
            'exam_marks'       => $examMarks,
            'marks_configs'    => $marksConfigs,
            'exam_schedules'   => $examSchedules,
            'timetables'       => $timetables,
            'substitutions'    => $substitutions,
            'student_subjects' => $studentSubjects,
            'grade_locks'      => $gradeLocks,
            'fee_structures'   => $feeStructures,
        ];
    }

    public function getLinkedRecordsCount(): int
    {
        return $this->getLinkedRecordsBreakdown()['total'];
    }

    public function canBeDeleted(): bool
    {
        return $this->getLinkedRecordsCount() === 0;
    }
}
