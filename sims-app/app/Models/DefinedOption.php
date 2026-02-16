<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefinedOption extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'name'];

    public function scopeSports($query)
    {
        return $query->where('type', 'sport');
    }

    public function scopeActivities($query)
    {
        return $query->where('type', 'activity');
    }
}
