<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeRecordItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_record_id',
        'fee_head_id',
        'fee_head_name',
        'subject_name',
        'amount',
        'category',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function record()
    {
        return $this->belongsTo(FeeRecord::class, 'fee_record_id');
    }

    public function head()
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }
}
