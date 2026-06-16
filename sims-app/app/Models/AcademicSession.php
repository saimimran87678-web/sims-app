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
                
                // Update teachers' assigned classes
                static::updateTeacherClassAssignments($session);
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

    public static function updateTeacherClassAssignments($activeSession)
    {
        if (!$activeSession || !$activeSession->is_active) {
            return;
        }

        // Get all users who currently have a class_id
        $users = \App\Models\User::whereNotNull('class_id')->get();

        foreach ($users as $user) {
            // Find the class they are currently assigned to in the database
            $currentClass = \Illuminate\Support\Facades\DB::table('classes')
                ->where('id', $user->getRawOriginal('class_id'))
                ->first();

            if ($currentClass) {
                // If it's already in the active session, do nothing
                if ($currentClass->academic_session_id == $activeSession->id) {
                    continue;
                }

                // Look for a class in the newly active session with the same name
                $newClass = \Illuminate\Support\Facades\DB::table('classes')
                    ->where('academic_session_id', $activeSession->id)
                    ->where('name', $currentClass->name)
                    ->first();

                if ($newClass) {
                    // Update user's class_id in the DB to the new class's ID
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $user->id)
                        ->update(['class_id' => $newClass->id]);
                } else {
                    // Reset to null if no matching class exists in the new session
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $user->id)
                        ->update(['class_id' => null]);
                }
            }
        }
    }
}
