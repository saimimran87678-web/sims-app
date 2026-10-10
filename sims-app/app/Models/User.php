<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Classes;
use App\Models\AcademicSession;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles;

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
        'class_id',
        'class_subject',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
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
            // User 1 (Super Admin / School Owner) is always active
            $q->where('users.id', 1)
              ->orWhere(function($sub) use ($sessionId, $shiftType) {
                  $sub->whereHas('academicSessions', function($sq) use ($sessionId, $shiftType) {
                      $sq->where('session_user.academic_session_id', $sessionId)
                         ->where('session_user.is_active', true);
                      if ($shiftType !== 'both') {
                          $sq->where(function($ssq) use ($shiftType) {
                              $ssq->where('session_user.allowed_shifts', 'both')
                                  ->orWhere('session_user.allowed_shifts', $shiftType);
                          });
                      }
                  })
                  ->orWhere(function($adminQ) use ($sessionId) {
                      // Staff admins without an explicit session_user entry in this session default to active
                      $adminQ->where('role', 'admin')
                             ->whereDoesntHave('academicSessions', function($sq) use ($sessionId) {
                                 $sq->where('session_user.academic_session_id', $sessionId);
                             });
                  });
              });
        });
    }
}

