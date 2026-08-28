<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'program_subjects')
            ->withPivot('subject_type', 'sort_order')
            ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'program_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function compulsorySubjects()
    {
        return $this->subjects()->wherePivot('subject_type', 'compulsory')->get();
    }

    public function electiveSubjects()
    {
        return $this->subjects()->wherePivot('subject_type', 'elective')->get();
    }
}
