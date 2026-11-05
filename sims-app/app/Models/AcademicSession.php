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
        'is_archived',
        'archived_reason',
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
            self::flushActiveSessionMemo();
            if ($session->is_active) {
                static::where('id', '!=', $session->id)->update(['is_active' => false]);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Determines whether an academic session is historical and locked against mutative changes.
     * Concluded sessions (is_active = false or is_archived = true) are locked archives.
     */
    public static function isSessionLocked($sessionId = null): bool
    {
        $id = $sessionId ?: self::getActiveSessionId();
        if (!$id) return false;

        $session = static::find($id);
        if (!$session) return false;

        // An active session is always open for operations
        if ($session->is_active) {
            return false;
        }

        // If explicitly archived, it is sealed
        if ($session->is_archived) {
            return true;
        }

        // If end_date is in the past, the academic session has concluded and is sealed
        if ($session->end_date && \Carbon\Carbon::parse($session->end_date)->endOfDay()->isPast()) {
            return true;
        }

        return false;
    }

    /** @var array{key:string,at:float,id:mixed}|null Answer of the last lookup, kept for a moment. */
    private static ?array $activeIdMemo = null;

    public static function flushActiveSessionMemo(): void
    {
        self::$activeIdMemo = null;
    }

    public static function getActiveSessionId()
    {
        $key = (auth()->id() ?? 0).'|'.session('selected_academic_session_id').'|'.session('current_session_id').'|'.session('selected_session_id');
        $memo = self::$activeIdMemo;
        if ($memo !== null && $memo['key'] === $key && (microtime(true) - $memo['at']) < 2.0) {
            return $memo['id'];
        }

        $id = static::lookupActiveSessionId();
        self::$activeIdMemo = ['key' => $key, 'at' => microtime(true), 'id' => $id];

        return $id;
    }

    private static function lookupActiveSessionId()
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

        // Session-switching fallback
        if (session()->has('selected_session_id')) {
            $id = session('selected_session_id');
            if (static::active()->where('id', $id)->exists()) {
                return $id;
            }
            session()->forget('selected_session_id');
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
