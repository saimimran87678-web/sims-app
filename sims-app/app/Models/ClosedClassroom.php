<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClosedClassroom extends Model
{
    protected $fillable = ['class_id', 'date', 'reason'];

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
