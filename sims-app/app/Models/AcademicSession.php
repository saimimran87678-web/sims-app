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

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($session) {
            if ($session->is_active) {
                // If it's a parent session, deactivate all other parent sessions and THEIR children.
                if (is_null($session->parent_id)) {
                    static::where('id', '!=', $session->id)->whereNull('parent_id')->update(['is_active' => false]);
                    static::whereNotNull('parent_id')->where('parent_id', '!=', $session->id)->update(['is_active' => false]);
                }
            }
        });
    }

    public static function getActiveSessionId()
    {
        // 1. If a specific session context is explicitly set for the user (via login or session shifter), use it.
        if (session()->has('current_session_id')) {
            return session('current_session_id');
        }
        
        // Admin overrides for viewing other sessions
        if (session()->has('selected_academic_session_id') && auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->hasRole('Super Admin'))) {
            return session('selected_academic_session_id');
        }

        // 2. Default: Find the currently active parent session (or Morning shift)
        $activeSession = static::where('is_active', true)
                               ->where(function($query) {
                                   $query->whereNull('parent_id')->orWhere('shift_type', 'Morning');
                               })
                               ->first();

        if (!$activeSession) {
            $activeSession = static::where('is_active', true)->first();
        }

        if (!$activeSession) {
            $activeSession = static::orderBy('start_date', 'desc')->first();
        }

        return $activeSession ? $activeSession->id : null;
    }
}
