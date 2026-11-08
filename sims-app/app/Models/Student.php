<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Guardian;

use App\Traits\LogsActivity;

class Student extends Model
{
    use LogsActivity, SoftDeletes;

    public function getActivitySubjectLabel(): string
    {
        return "Student: {$this->name}" . ($this->roll_no ? " (Roll #{$this->roll_no})" : '');
    }

    public const SPORTS_OPTIONS = ['Cricket', 'Football', 'Hockey', 'Badminton', 'Table Tennis', 'Volleyball', 'Basketball', 'Athletics'];
    public const ACTIVITY_OPTIONS = ['Naat', 'Tilawat', 'Speech (Urdu)', 'Speech (English)', 'Debate', 'Quiz', 'Drama'];
    public const TRANSPORT_OPTIONS = ['none' => 'None', 'school_bus' => 'School Bus', 'private_van' => 'Private Van', 'car' => 'Car', 'bike' => 'Bike'];
    public const BUS_OPTIONS = ['135', '147'];
    
    public $tempClassId;
    public $tempRollNo;
    public $tempSectionId;

    protected $fillable = [
        'name',
        'roll_no',
        'admission_no',
        'b_form_number',
        'class_id',
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
        'original_admission_date',
        'address',
        'status',
        'status_reason',
        'father_cnic',
        'guardian_cnic',
        'guardian_relation',
        'is_guardian_primary',
        'sibling_concession_status',
        'custom_concession_amount',
        'rfid_tag',
        'card_issued_at',
        'card_status',
        'father_cnic_hash',
        'guardian_cnic_hash',
        'b_form_hash',
        'phone_hash',
    ];

    public static function hashBlindIndex(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);
        $normalized = !empty($digits) ? $digits : strtolower(trim((string) $value));
        return hash_hmac('sha256', $normalized, config('app.key'));
    }

    public function setClassIdAttribute($value)
    {
        $this->tempClassId = $value;
    }

    public function setRollNoAttribute($value)
    {
        $this->tempRollNo = $value;
    }

    public function setSectionIdAttribute($value)
    {
        $this->tempSectionId = $value;
    }

    protected $casts = [
        'dob' => 'date',
        'admission_date' => 'date',
        'original_admission_date' => 'date',
        'is_guardian_primary' => 'boolean',
        'custom_concession_amount' => 'decimal:2',
        'card_issued_at' => 'datetime',
        'father_cnic' => 'encrypted',
        'guardian_cnic' => 'encrypted',
        'b_form_number' => 'encrypted',
        'phone' => 'encrypted',
        'address' => 'encrypted',
    ];

    protected static function booted()
    {
        static::saving(function ($student) {
            if ($student->isDirty('father_cnic') || (empty($student->father_cnic_hash) && !empty($student->father_cnic))) {
                $student->father_cnic_hash = self::hashBlindIndex($student->father_cnic);
            }
            if ($student->isDirty('guardian_cnic') || (empty($student->guardian_cnic_hash) && !empty($student->guardian_cnic))) {
                $student->guardian_cnic_hash = self::hashBlindIndex($student->guardian_cnic);
            }
            if ($student->isDirty('b_form_number') || (empty($student->b_form_hash) && !empty($student->b_form_number))) {
                $student->b_form_hash = self::hashBlindIndex($student->b_form_number);
            }
            if ($student->isDirty('phone') || (empty($student->phone_hash) && !empty($student->phone))) {
                $student->phone_hash = self::hashBlindIndex($student->phone);
            }
        });

        static::saved(function ($student) {
            $hasFather = !empty($student->attributes['father_name'] ?? null) 
                      || !empty($student->father_cnic) 
                      || !empty($student->father_cnic_hash);
            $hasGuardian = !empty($student->guardian_cnic) 
                        || !empty($student->guardian_cnic_hash);
            $hasPhone = !empty($student->phone);

            if ($hasFather || $hasGuardian || $hasPhone) {
                $cnic = $student->is_guardian_primary 
                    ? ($student->guardian_cnic ?: $student->father_cnic)
                    : ($student->father_cnic ?: $student->guardian_cnic);
                $cnicHash = $student->is_guardian_primary 
                    ? ($student->guardian_cnic_hash ?: $student->father_cnic_hash)
                    : ($student->father_cnic_hash ?: $student->guardian_cnic_hash);
                $name = ($student->attributes['father_name'] ?? null) ?: ($student->name ? 'Guardian of ' . $student->name : 'Guardian');
                $relation = $student->guardian_relation ?: ($student->is_guardian_primary ? 'Guardian' : 'Father');

                $guardian = null;
                if (!empty($cnicHash)) {
                    $guardian = Guardian::where('cnic_hash', $cnicHash)->first();
                }

                if (!$guardian) {
                    $guardian = Guardian::create([
                        'name' => $name,
                        'cnic' => $cnic,
                        'cnic_hash' => $cnicHash,
                        'phone' => $student->phone,
                        'phone_hash' => $student->phone_hash,
                        'address' => $student->address,
                        'whatsapp_opt_in' => true,
                    ]);
                } else {
                    $dirty = false;
                    if (!empty($student->phone) && empty($guardian->phone)) {
                        $guardian->phone = $student->phone;
                        $dirty = true;
                    }
                    if (!empty($student->address) && empty($guardian->address)) {
                        $guardian->address = $student->address;
                        $dirty = true;
                    }
                    if ($dirty) {
                        $guardian->save();
                    }
                }

                $exists = \Illuminate\Support\Facades\DB::table('student_guardian')
                    ->where('student_id', $student->id)
                    ->where('guardian_id', $guardian->id)
                    ->exists();

                if (!$exists) {
                    \Illuminate\Support\Facades\DB::table('student_guardian')->insert([
                        'student_id' => $student->id,
                        'guardian_id' => $guardian->id,
                        'relation' => $relation,
                        'is_primary' => true,
                        'is_emergency' => true,
                        'can_pickup' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        static::deleting(function ($student) {
            if ($student->isForceDeleting()) {
                $hasFinancial = $student->feeRecords()->exists() 
                             || $student->feePayments()->exists()
                             || \Illuminate\Support\Facades\DB::table('fee_invoices')->where('student_id', $student->id)->exists();

                $hasAcademic = $student->enrollments()->withTrashed()->exists() 
                            || \Illuminate\Support\Facades\DB::table('exam_marks')->where('student_id', $student->id)->exists()
                            || \Illuminate\Support\Facades\DB::table('attendances')->where('student_id', $student->id)->exists();

                if ($hasFinancial || $hasAcademic) {
                    throw new \DomainException("Institutional integrity violation: Student has archived financial or academic records and cannot be permanently erased.");
                }
            }
        });

        static::created(function ($student) {
            $classId = $student->tempClassId ?? null;
            $rollNo = $student->tempRollNo ?? null;
            $sectionId = $student->tempSectionId ?? null;
            $status = $student->attributes['status'] ?? 'active';

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
                        'section_id' => $sectionId,
                        'roll_number' => $rollNo,
                        'status' => $status,
                    ]
                );
            }
        });
    }

    public function schoolLeavingCertificates()
    {
        return $this->hasMany(SchoolLeavingCertificate::class);
    }

    public function feeWaiverApplications()
    {
        return $this->hasMany(FeeWaiverApplication::class);
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

    public function getTerminalClassAttribute(): ?Classes
    {
        if ($this->relationLoaded('class') && $this->class) {
            return $this->class;
        }

        if ($this->relationLoaded('enrollments')) {
            $latest = $this->enrollments
                ->filter(fn($e) => !empty($e->class_id))
                ->sortByDesc('academic_session_id')
                ->first();
            if ($latest && $latest->relationLoaded('class') && $latest->class) {
                return $latest->class;
            }
        }

        $latestEnrollment = $this->enrollments()
            ->whereNotNull('class_id')
            ->orderByDesc('academic_session_id')
            ->orderByDesc('id')
            ->with('class')
            ->first();

        return $latestEnrollment?->class;
    }

    public function getTerminalClassNameAttribute(): string
    {
        return $this->terminal_class?->name ?? 'Unassigned';
    }

    public function section()
    {
        return $this->hasOneThrough(
            Section::class,
            Enrollment::class,
            'student_id', // Foreign key on enrollments table...
            'id',         // Foreign key on sections table...
            'id',         // Local key on students table...
            'section_id'  // Local key on enrollments table...
        )->where('enrollments.academic_session_id', \App\Models\AcademicSession::getActiveSessionId());
    }

    public function getSectionIdAttribute()
    {
        if (array_key_exists('section_id', $this->attributes) && $this->attributes['section_id']) {
            return $this->attributes['section_id'];
        }

        $cachedSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($cachedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shift = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $cachedShiftType = ($shift === 'both') ? 'morning' : $shift;

        $sectionId = $this->enrollments()
            ->where('academic_session_id', $cachedSessionId)
            ->where('shift_type', $cachedShiftType)
            ->value('section_id');

        if ($sectionId) {
            return $sectionId;
        }

        return $this->enrollments()
            ->whereNotNull('section_id')
            ->orderByDesc('academic_session_id')
            ->orderByDesc('id')
            ->value('section_id');
    }

    public function getRollNoAttribute()
    {
        if (array_key_exists('roll_no', $this->attributes) && $this->attributes['roll_no']) {
            return $this->attributes['roll_no'];
        }

        $cachedSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($cachedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shift = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $cachedShiftType = ($shift === 'both') ? 'morning' : $shift;

        $rollNo = $this->enrollments()
            ->where('academic_session_id', $cachedSessionId)
            ->where('shift_type', $cachedShiftType)
            ->value('roll_number');

        if ($rollNo) {
            return $rollNo;
        }

        return $this->enrollments()
            ->whereNotNull('roll_number')
            ->orderByDesc('academic_session_id')
            ->orderByDesc('id')
            ->value('roll_number');
    }

    public function getClassIdAttribute()
    {
        if (array_key_exists('class_id', $this->attributes) && $this->attributes['class_id']) {
            return $this->attributes['class_id'];
        }

        $cachedSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($cachedSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shift = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        $cachedShiftType = ($shift === 'both') ? 'morning' : $shift;

        $classId = $this->enrollments()
            ->where('academic_session_id', $cachedSessionId)
            ->where('shift_type', $cachedShiftType)
            ->value('class_id');

        if ($classId) {
            return $classId;
        }

        return $this->enrollments()
            ->whereNotNull('class_id')
            ->orderByDesc('academic_session_id')
            ->orderByDesc('id')
            ->value('class_id');
    }

    public function getPhotoUrlAttribute(): string
    {
        return route('student.photo', $this->id);
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

    public function guardians()
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian')
            ->withPivot(['id', 'relation', 'is_primary', 'is_emergency', 'can_pickup', 'concession_percentage'])
            ->withTimestamps();
    }

    public function primaryGuardians()
    {
        return $this->guardians()->wherePivot('is_primary', true);
    }

    public function getPrimaryGuardianAttribute(): ?Guardian
    {
        if ($this->relationLoaded('guardians')) {
            return $this->guardians->firstWhere('pivot.is_primary', true) ?? $this->guardians->first();
        }
        return $this->guardians()->wherePivot('is_primary', true)->first() ?? $this->guardians()->first();
    }

    public function getFatherNameAttribute($value): ?string
    {
        return !empty($value) ? $value : $this->primaryGuardian?->name;
    }

    public function getFatherCnicAttribute($value): ?string
    {
        $decrypted = $value ? $this->castAttribute('father_cnic', $value) : null;
        return $decrypted ?: $this->primaryGuardian?->cnic;
    }

    public function getGuardianCnicAttribute($value): ?string
    {
        $decrypted = $value ? $this->castAttribute('guardian_cnic', $value) : null;
        return $decrypted ?: $this->primaryGuardian?->cnic;
    }

    public function getPhoneAttribute($value): ?string
    {
        $decrypted = $value ? $this->castAttribute('phone', $value) : null;
        return $decrypted ?: $this->primaryGuardian?->phone;
    }

    /**
     * Resolves the primary family CNIC based on guardianship precedence.
     */
    public function getPrimaryFamilyCnicAttribute(): ?string
    {
        return $this->is_guardian_primary 
            ? ($this->guardian_cnic ?: $this->father_cnic)
            : ($this->father_cnic ?: $this->guardian_cnic);
    }

    public function getPrimaryFamilyCnicHashAttribute(): ?string
    {
        return $this->is_guardian_primary
            ? ($this->guardian_cnic_hash ?: $this->father_cnic_hash)
            : ($this->father_cnic_hash ?: $this->guardian_cnic_hash);
    }

    /**
     * Sibling relationships: students sharing common guardians via normalized pivot table.
     */
    public function siblings()
    {
        $guardianIds = \Illuminate\Support\Facades\DB::table('student_guardian')
            ->where('student_id', $this->id)
            ->pluck('guardian_id');

        if ($guardianIds->isNotEmpty()) {
            return Student::whereHas('guardians', function ($q) use ($guardianIds) {
                    $q->whereIn('guardians.id', $guardianIds);
                })
                ->where('students.id', '!=', $this->id)
                ->where('students.status', 'active');
        }

        return $this->hasMany(Student::class, 'father_cnic_hash', 'father_cnic_hash')
            ->where('id', '!=', $this->id)
            ->where('status', 'active');
    }

    public function getSiblingsAttribute()
    {
        return $this->siblings()->get();
    }

    /**
     * Returns complete family unit including self, ordered chronologically by admission.
     */
    public function getFamilyUnitAttribute()
    {
        $guardianIds = \Illuminate\Support\Facades\DB::table('student_guardian')
            ->where('student_id', $this->id)
            ->pluck('guardian_id');

        if ($guardianIds->isNotEmpty()) {
            $members = Student::whereHas('guardians', function ($q) use ($guardianIds) {
                    $q->whereIn('guardians.id', $guardianIds);
                })
                ->where('students.status', 'active')
                ->orderBy('admission_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($members->isNotEmpty()) {
                return $members;
            }
        }

        $hash = $this->primary_family_cnic_hash;
        if (empty($hash)) {
            return collect([$this]);
        }

        $results = Student::where(function ($query) use ($hash) {
                $query->where('father_cnic_hash', $hash)
                      ->orWhere('guardian_cnic_hash', $hash);
            })
            ->where('status', 'active')
            ->orderBy('admission_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $results->isNotEmpty() ? $results : collect([$this]);
    }

    /**
     * Returns all children belonging to this family unit across all statuses (active, graduated, slc_issued).
     * Used for the family portal sibling switcher and family history.
     */
    public function getAllFamilyMembersAttribute()
    {
        $guardianIds = \Illuminate\Support\Facades\DB::table('student_guardian')
            ->where('student_id', $this->id)
            ->pluck('guardian_id');

        if ($guardianIds->isNotEmpty()) {
            $members = Student::whereHas('guardians', function ($q) use ($guardianIds) {
                    $q->whereIn('guardians.id', $guardianIds);
                })
                ->orderBy('admission_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($members->isNotEmpty()) {
                return $members;
            }
        }

        $hash = $this->primary_family_cnic_hash;
        if (empty($hash)) {
            return collect([$this]);
        }

        $results = Student::where(function ($query) use ($hash) {
                $query->where('father_cnic_hash', $hash)
                      ->orWhere('guardian_cnic_hash', $hash);
            })
            ->orderBy('admission_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $results->isNotEmpty() ? $results : collect([$this]);
    }

    /**
     * Determines sibling rank for concession calculation (0 = 1st child, 1 = 2nd child, etc.)
     */
    public function getSiblingRankAttribute(): int
    {
        $index = $this->family_unit->search(fn($student) => $student->id === $this->id);
        return $index !== false ? $index : 0;
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

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function todayAttendance()
    {
        return $this->hasOne(Attendance::class, 'student_id')->where('date', now()->format('Y-m-d'));
    }

    public function scopeHasRfid($query)
    {
        return $query->whereNotNull('rfid_tag')->where('rfid_tag', '!=', '');
    }

    public function scopeWithoutRfid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('rfid_tag')->orWhere('rfid_tag', '');
        });
    }
}
