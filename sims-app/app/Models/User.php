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
            ->withPivot('class_id', 'class_subject', 'is_active')
            ->withTimestamps();
    }
}
