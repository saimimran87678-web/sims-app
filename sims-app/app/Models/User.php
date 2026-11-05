<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Classes;
use App\Models\AcademicSession;

use App\Traits\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles, LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        return "User: {$this->name} ({$this->email})";
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::saved(function ($user) {
            $user->syncSpatieRoleFromRoleColumn();
        });
    }

    /**
     * Automatically sync Spatie roles based on users.role column
     */
    public function syncSpatieRoleFromRoleColumn(): void
    {
        $role = strtolower($this->role ?? '');
        if (!$role) return;

        // If the user already has Super Admin, preserve it on admin updates
        if ($this->hasRole('Super Admin') && ($role === 'admin' || $role === 'superadmin' || $role === 'super_admin')) {
            return;
        }

        $roleMap = [
            'admin' => \Spatie\Permission\Models\Role::where('name', 'Admin')->where('guard_name', 'web')->exists() ? 'Admin' : 'Super Admin',
            'superadmin' => 'Super Admin',
            'super_admin' => 'Super Admin',
            'teacher' => 'Teacher',
            'student' => 'Student',
            'data_entry' => 'Data Entry',
        ];

        if (isset($roleMap[$role])) {
            $targetRole = $roleMap[$role];
            if (\Spatie\Permission\Models\Role::where('name', $targetRole)->where('guard_name', 'web')->exists()) {
                $this->syncRoles([$targetRole]);
            }
        }
    }

    public function isSuperAdmin(): bool
    {
        return strtolower($this->role ?? '') === 'superadmin' 
            || strtolower($this->role ?? '') === 'super_admin' 
            || $this->hasRole('Super Admin');
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() 
            || strtolower($this->role ?? '') === 'admin' 
            || $this->hasRole('Admin');
    }

    public function isTeacher(): bool
    {
        return strtolower($this->role ?? '') === 'teacher' || $this->hasRole('Teacher');
    }

    public function isStudent(): bool
    {
        return strtolower($this->role ?? '') === 'student' || $this->hasRole('Student');
    }

    /**
     * Teacher's assigned class for the active session.
     */
    public function class()
    {
        return $this->hasOneThrough(
            Classes::class,
            SessionUser::class,
            'user_id',
            'id',
            'id',
            'class_id'
        )->where('session_user.academic_session_id', AcademicSession::getActiveSessionId());
    }

    public function getClassAttribute()
    {
        $classId = $this->getSessionClassId();
        return $classId ? Classes::withoutGlobalScope('active_session')->find($classId) : null;
    }

    public function getClassIdAttribute()
    {
        return $this->getSessionClassId();
    }

    public function getClassSubjectAttribute()
    {
        return $this->getSessionClassSubject();
    }

    /**
     * Get the teacher's class_id from the session_user pivot for the given (or active) session.
     */
    public function getSessionClassId($sessionId = null)
    {
        $sessionId = $sessionId ?? AcademicSession::getActiveSessionId();
        if (!$sessionId) return null;

        return \Illuminate\Support\Facades\DB::table('session_user')
            ->where('user_id', $this->id)
            ->where('academic_session_id', $sessionId)
            ->value('class_id');
    }

    /**
     * Get the teacher's class_subject from the session_user pivot for the given (or active) session.
     */
    public function getSessionClassSubject($sessionId = null)
    {
        $sessionId = $sessionId ?? AcademicSession::getActiveSessionId();
        if (!$sessionId) return null;

        return \Illuminate\Support\Facades\DB::table('session_user')
            ->where('user_id', $this->id)
            ->where('academic_session_id', $sessionId)
            ->value('class_subject');
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function academicSessions()
    {
        return $this->belongsToMany(AcademicSession::class, 'session_user')
            ->withPivot('class_id', 'class_subject', 'is_active', 'allowed_shifts')
            ->withTimestamps();
    }

    public function teacherAttendances()
    {
        return $this->hasMany(TeacherAttendance::class, 'teacher_id');
    }

    public function substitutionsAsAbsent()
    {
        return $this->hasMany(Substitution::class, 'absent_teacher_id');
    }

    public function substitutionsAsSubstitute()
    {
        return $this->hasMany(Substitution::class, 'substitute_teacher_id');
    }

    /**
     * Scope a query to only include users active in the given academic session and shift.
     */
    public function scopeActiveInSession($query, $sessionId, $shiftType = 'both')
    {
        if (!$sessionId) {
            return $query;
        }

        return $query->where(function($q) use ($sessionId, $shiftType) {
            // Admins are active across all shifts unless explicitly deactivated in this session
            $q->where(function($adminQ) use ($sessionId) {
                $adminQ->where('role', 'admin')
                       ->where(function($sub) use ($sessionId) {
                           $sub->whereDoesntHave('academicSessions', function($sq) use ($sessionId) {
                               $sq->where('session_user.academic_session_id', $sessionId);
                           })
                           ->orWhereHas('academicSessions', function($sq) use ($sessionId) {
                               $sq->where('session_user.academic_session_id', $sessionId)
                                  ->where('session_user.is_active', true);
                           });
                       });
            })
            // Teachers are scoped to session and allowed shifts
            ->orWhere(function($teacherQ) use ($sessionId, $shiftType) {
                $teacherQ->where('role', '!=', 'admin')
                         ->whereHas('academicSessions', function($sq) use ($sessionId, $shiftType) {
                             $sq->where('session_user.academic_session_id', $sessionId)
                                ->where('session_user.is_active', true);
                             if ($shiftType !== 'both') {
                                 $sq->where(function($ssq) use ($shiftType) {
                                     $ssq->where('session_user.allowed_shifts', 'both')
                                         ->orWhere('session_user.allowed_shifts', $shiftType);
                                 });
                             }
                         });
            });
        });
    }
}

