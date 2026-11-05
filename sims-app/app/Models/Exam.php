<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    // Lifecycle Constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ENTERED = 'entered';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'name',
        'type',
        'description',
        'academic_session_id',
        'start_date',
        'end_date',
        'is_active',
        'datesheet_instructions_heading',
        'datesheet_instructions',
        'lifecycle_status',
        'published_at',
        'published_by_user_id',
        'locked_at',
        'locked_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    // Relationships
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }

    public function examMarks(): HasMany
    {
        return $this->hasMany(ExamMark::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('lifecycle_status', self::STATUS_PUBLISHED);
    }

    public function scopeMarksEntryOpen($query)
    {
        return $query->whereIn('lifecycle_status', [self::STATUS_DRAFT, self::STATUS_ENTERED]);
    }

    // Lifecycle Status Checks
    public function isDraft(): bool
    {
        return ($this->lifecycle_status ?? self::STATUS_DRAFT) === self::STATUS_DRAFT;
    }

    public function isEntered(): bool
    {
        return $this->lifecycle_status === self::STATUS_ENTERED;
    }

    public function isLocked(): bool
    {
        return $this->lifecycle_status === self::STATUS_LOCKED;
    }

    public function isPublished(): bool
    {
        return $this->lifecycle_status === self::STATUS_PUBLISHED;
    }

    /**
     * Determines if marks can be entered or modified for this exam.
     */
    public function canEnterMarks(): bool
    {
        return !in_array($this->lifecycle_status, [self::STATUS_LOCKED, self::STATUS_PUBLISHED]);
    }

    // Lifecycle State Transitions
    public function openForEntry(?User $user = null): void
    {
        $this->update([
            'lifecycle_status' => self::STATUS_ENTERED,
        ]);
    }

    public function lock(?User $user = null): void
    {
        $this->update([
            'lifecycle_status' => self::STATUS_LOCKED,
            'locked_at' => now(),
            'locked_by_user_id' => $user?->id ?? auth()->id(),
        ]);
    }

    public function publish(?User $user = null): void
    {
        $this->update([
            'lifecycle_status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by_user_id' => $user?->id ?? auth()->id(),
        ]);
    }

    public function revertToDraft(?User $user = null): void
    {
        $this->update([
            'lifecycle_status' => self::STATUS_DRAFT,
            'published_at' => null,
            'published_by_user_id' => null,
            'locked_at' => null,
            'locked_by_user_id' => null,
        ]);
    }

    // Dynamic Status Accessor (Temporal + Lifecycle)
    public function getStatusAttribute()
    {
        $now = now()->format('Y-m-d');
        
        if ($now < $this->start_date) {
            return 'Upcoming';
        }
        
        if ($now >= $this->start_date && $now <= $this->end_date) {
            return 'Ongoing';
        }
        
        if ($now > $this->end_date) {
            return 'Completed';
        }
        
        return 'Unknown';
    }

    public function getLifecycleLabelAttribute(): string
    {
        return match ($this->lifecycle_status) {
            self::STATUS_ENTERED => 'Marks Entry Open',
            self::STATUS_LOCKED => 'Locked (Auditing)',
            self::STATUS_PUBLISHED => 'Published',
            default => 'Draft',
        };
    }
}
