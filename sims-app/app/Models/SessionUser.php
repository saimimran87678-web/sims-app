<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SessionUser extends Pivot
{
    protected $table = 'session_user';
    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'academic_session_id',
        'is_active',
        'is_primary',
        'class_id',
        'class_subject',
        'allowed_shifts',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
