<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    protected $table = 'grade_scales';

    protected $fillable = [
        'academic_session_id',
        'grade',
        'percentage_from',
        'percentage_to',
        'gpa_point',
        'remarks',
        'color',
        'is_passing',
        'order_seq',
    ];

    protected $casts = [
        'percentage_from' => 'decimal:2',
        'percentage_to' => 'decimal:2',
        'gpa_point' => 'decimal:2',
        'is_passing' => 'boolean',
        'order_seq' => 'integer',
    ];

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_seq', 'asc')->orderBy('percentage_from', 'desc');
    }

    /**
     * Scope to fetch grade scales for an academic session, falling back to canonical defaults.
     */
    public static function getScalesForSession(?int $sessionId = null)
    {
        if ($sessionId) {
            $sessionScales = static::where('academic_session_id', $sessionId)
                ->ordered()
                ->get();

            if ($sessionScales->isNotEmpty()) {
                return $sessionScales;
            }
        }

        return static::whereNull('academic_session_id')
            ->ordered()
            ->get();
    }
}
