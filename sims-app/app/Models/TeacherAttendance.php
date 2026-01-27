<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'status',
        'remarks',
    ];

    protected $casts = [
        // 'date' => 'date', // Removing cast to fix updateOrCreate comparison issues
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function substitutions()
    {
        return $this->hasMany(Substitution::class);
    }
}
