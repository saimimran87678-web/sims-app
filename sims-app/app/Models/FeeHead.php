<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'academic_session_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function feeOverrides()
    {
        return $this->hasMany(StudentFeeOverride::class);
    }
}
