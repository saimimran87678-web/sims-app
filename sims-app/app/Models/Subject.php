<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['class_id', 'name', 'code'];



    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }



    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_subjects')
            ->withPivot('subject_type', 'sort_order')
            ->withTimestamps();
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }
}
