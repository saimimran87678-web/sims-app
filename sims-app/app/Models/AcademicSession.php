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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($session) {
            if ($session->is_active) {
                // Deactivate all other sessions (using raw update to avoid recursion)
                static::where('id', '!=', $session->id)->update(['is_active' => false]);
                
                // Update teachers' assigned classes
                static::updateTeacherClassAssignments($session);
            }
        });
    }

    public static function getActiveSessionId()
    {
        // For non-admin/non-Super Admin users (e.g. teachers), always scope to the database active session.
        if (auth()->check() && auth()->user()->role !== 'admin' && !auth()->user()->hasRole('Super Admin')) {
            $activeSessionId = static::where('is_active', true)->value('id');
            if (!$activeSessionId) {
                $activeSessionId = static::orderBy('start_date', 'desc')->value('id');
            }
            return $activeSessionId;
        }

        if (session()->has('selected_academic_session_id')) {
            return session('selected_academic_session_id');
        }

        $activeSessionId = static::where('is_active', true)->value('id');
        if (!$activeSessionId) {
            $activeSessionId = static::orderBy('start_date', 'desc')->value('id');
        }

        return $activeSessionId;
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
