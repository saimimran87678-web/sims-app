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
     * Get the class ID attribute, dynamically mapping it to the active session.
     */
    public function getClassIdAttribute($value)
    {
        if (!$value) {
            return null;
        }

        try {
            // Fetch active session ID
            $activeSessionId = AcademicSession::getActiveSessionId();

            if (!$activeSessionId) {
                return $value;
            }

            // Check if the current class_id belongs to the active session
            $currentClass = \Illuminate\Support\Facades\DB::table('classes')
                ->where('id', $value)
                ->first();

            if ($currentClass && $currentClass->academic_session_id == $activeSessionId) {
                return $value;
            }

            // If it belongs to a different session, find the class with the same name in the active session
            if ($currentClass) {
                $matchingClassId = \Illuminate\Support\Facades\DB::table('classes')
                    ->where('academic_session_id', $activeSessionId)
                    ->where('name', $currentClass->name)
                    ->value('id');

                if ($matchingClassId) {
                    return $matchingClassId;
                }
            }
        } catch (\Throwable $e) {
            // Table might not exist yet during migrations/seeding
            return $value;
        }

        return null;
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
