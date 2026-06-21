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

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }
}
