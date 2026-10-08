<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Classes;
use App\Models\Section;

class Student extends Model
{
    public const SPORTS_OPTIONS = ['Cricket', 'Football', 'Hockey', 'Badminton', 'Table Tennis', 'Volleyball', 'Basketball', 'Athletics'];
    public const ACTIVITY_OPTIONS = ['Naat', 'Tilawat', 'Speech (Urdu)', 'Speech (English)', 'Debate', 'Quiz', 'Drama'];
    public const TRANSPORT_OPTIONS = ['none' => 'None', 'school_bus' => 'School Bus', 'private_van' => 'Private Van', 'car' => 'Car', 'bike' => 'Bike'];
    public const BUS_OPTIONS = ['135', '147'];
    
    public $tempClassId;
    public $tempRollNo;

    protected $fillable = [
        'name',
        'roll_no',
        'admission_no',
        'class_id',
        'section_id',
        'father_name',
        'phone',
        'email',
        'gender',
        'dob', // already present in schema but maybe not in form yet
        'sports',
        'extra_curriculars',
        'transport_mode',
        'vehicle_number',
        'profile_photo_path',
        'admission_date',
        'address',
        'status',
    ];

    public function setClassIdAttribute($value)
    {
        $this->tempClassId = $value;
    }

    public function setRollNoAttribute($value)
    {
        $this->tempRollNo = $value;
    }

    protected $casts = [
        'dob' => 'date',
        'admission_date' => 'date',
    ];

    protected static function booted()
    {
        static::created(function ($student) {
            $classId = $student->tempClassId ?? request('class_id') ?? null;
            $rollNo = $student->tempRollNo ?? request('roll_no') ?? null;
            $status = request('status') ?? $student->attributes['status'] ?? 'active';

            if ($classId) {
                $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
                $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
                $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');

                $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
                if ($shiftType === 'both') {
                    $shiftType = 'morning';
                }

                \App\Models\Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_session_id' => $activeSessionId,
                        'shift_type' => $shiftType,
                    ],
                    [
                        'class_id' => $classId,
                        'roll_number' => $rollNo,
                        'status' => $status,
                    ]
                );
            }
        });
    }

    public function class()
    {
        return $this->hasOneThrough(
            Classes::class,
            Enrollment::class,
            'student_id', // Foreign key on enrollments table...
            'id',         // Foreign key on classes table...
            'id',         // Local key on students table...
            'class_id'    // Local key on enrollments table...
        )->where('enrollments.academic_session_id', \App\Models\AcademicSession::getActiveSessionId());
    }

    public function getRollNoAttribute()
    {
        if (array_key_exists('roll_no', $this->attributes)) {
            return $this->attributes['roll_no'];
        }

        static $cachedSessionId = null;
        static $cachedShiftType = null;
        if ($cachedSessionId === null) {
            $cachedSessionId = \App\Models\AcademicSession::getActiveSessionId();
            $sessionObj = \App\Models\AcademicSession::find($cachedSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $shift = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
            $cachedShiftType = ($shift === 'both') ? 'morning' : $shift;
        }

        return $this->enrollments()
            ->where('academic_session_id', $cachedSessionId)
            ->where('shift_type', $cachedShiftType)
            ->value('roll_number');
    }

    public function getClassIdAttribute()
    {
        if (array_key_exists('class_id', $this->attributes)) {
            return $this->attributes['class_id'];
        }

        static $cachedSessionId = null;
        static $cachedShiftType = null;
        if ($cachedSessionId === null) {
            $cachedSessionId = \App\Models\AcademicSession::getActiveSessionId();
            $sessionObj = \App\Models\AcademicSession::find($cachedSessionId);
            $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
            $shift = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
            $cachedShiftType = ($shift === 'both') ? 'morning' : $shift;
        }

        return $this->enrollments()
            ->where('academic_session_id', $cachedSessionId)
            ->where('shift_type', $cachedShiftType)
            ->value('class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subject', 'student_id', 'subject_id');
    }

    public function feeRecords()
    {
        return $this->hasMany(FeeRecord::class);
    }

    public function feePayments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public function feeOverrides()
    {
        return $this->hasMany(StudentFeeOverride::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollments($sessionId)
    {
        return $this->enrollments()->forSession($sessionId)->active()->get();
    }

    public function isEnrolledInShift($sessionId, $shift): bool
    {
        return $this->enrollments()->forSession($sessionId)->forShift($shift)->active()->exists();
    }

    public function morningEnrollment($sessionId): ?Enrollment
    {
        return $this->enrollments()->forSession($sessionId)->morning()->active()->first();
    }

    public function eveningEnrollment($sessionId): ?Enrollment
    {
        return $this->enrollments()->forSession($sessionId)->evening()->active()->first();
    }

    public function enrollmentFor($sessionId, $shift): ?Enrollment
    {
        return $this->enrollments()->forSession($sessionId)->forShift($shift)->active()->first();
    }

    public function isDualShift($sessionId): bool
    {
        return $this->enrollments()->forSession($sessionId)->active()->count() > 1;
    }
}
