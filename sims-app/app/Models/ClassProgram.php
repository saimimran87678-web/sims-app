<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassProgram extends Pivot
{
    protected $table = 'class_programs';

    protected $fillable = [
        'class_id',
        'program_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
