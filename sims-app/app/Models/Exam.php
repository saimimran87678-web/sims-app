<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'academic_session_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // 'start_date' => 'date', // Let's keep as string or ensure Carbon access
    ];

    // Relationships
    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function schedules()
    {
        return $this->hasMany(ExamSchedule::class);
    }

    // Dynamic Status Accessor
    public function getStatusAttribute()
    {
        $now = now()->format('Y-m-d');
        
        if ($now < $this->start_date) {
            return 'Upcoming';
        }
        
        if ($now >= $this->start_date && $now <= $this->end_date) {
            return 'Ongoing';
        }
        
        if ($now > $this->end_date) {
            return 'Completed';
        }
        
        return 'Unknown';
    }
}
