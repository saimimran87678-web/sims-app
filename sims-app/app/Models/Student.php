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

    protected $casts = [
        'dob' => 'date',
        'admission_date' => 'date',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
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
}
