<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'parent_id',
        'shift_type',
    ];

    public function parent()
    {
        return $this->belongsTo(AcademicSession::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AcademicSession::class, 'parent_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'session_user')
            ->withPivot('is_active', 'is_primary')
            ->withTimestamps();
    }

    public function classes()
    {
        return $this->hasMany(Classes::class, 'academic_session_id');
    }

    public function feeHeads()
    {
        return $this->hasMany(FeeHead::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function feeRecords()
    {
        return $this->hasMany(FeeRecord::class);
    }

    public function feeOverrides()
    {
        return $this->hasMany(StudentFeeOverride::class);
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($session) {
            if ($session->is_active) {
                static::where('id', '!=', $session->id)->update(['is_active' => false]);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public static function getActiveSessionId()
    {
        // 1. Admin overrides for viewing other sessions
        if (session()->has('selected_academic_session_id') && auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->hasRole('Super Admin'))) {
            $id = session('selected_academic_session_id');
            if (static::active()->where('id', $id)->exists()) {
                return $id;
            }
            session()->forget('selected_academic_session_id');
        }

        // Backward-compatibility fallback
        if (session()->has('current_session_id')) {
            $id = session('current_session_id');
            if (static::active()->where('id', $id)->exists()) {
                return $id;
            }
            session()->forget('current_session_id');
        }

        // 2. Default: Find the currently active system session
        $activeSession = static::active()
                               ->where('is_active', true)
                               ->first();

        if (!$activeSession) {
            $activeSession = static::active()->orderBy('start_date', 'desc')->first();
        }

        return $activeSession ? $activeSession->id : null;
    }
}
