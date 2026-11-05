<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\LogsActivity;

class Guardian extends Model
{
    use LogsActivity;

    public function getActivitySubjectLabel(): string
    {
        return "Guardian: {$this->name}";
    }

    public const RELATION_OPTIONS = [
        'Father',
        'Mother',
        'Uncle',
        'Aunt',
        'Grandparent',
        'Brother',
        'Sister',
        'Guardian',
        'Other',
    ];

    protected $fillable = [
        'name',
        'cnic',
        'cnic_hash',
        'phone',
        'phone_hash',
        'email',
        'occupation',
        'address',
        'whatsapp_opt_in',
    ];

    protected $casts = [
        'cnic' => 'encrypted',
        'phone' => 'encrypted',
        'address' => 'encrypted',
        'whatsapp_opt_in' => 'boolean',
    ];

    /**
     * Compute HMAC-SHA256 blind index hash for exact matching and index queries.
     */
    public static function hashBlindIndex(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);
        $normalized = !empty($digits) ? $digits : strtolower(trim((string) $value));
        return hash_hmac('sha256', $normalized, config('app.key'));
    }

    protected static function booted()
    {
        static::saving(function ($guardian) {
            if ($guardian->isDirty('cnic') || (empty($guardian->cnic_hash) && !empty($guardian->cnic))) {
                $guardian->cnic_hash = self::hashBlindIndex($guardian->cnic);
            }
            if ($guardian->isDirty('phone') || (empty($guardian->phone_hash) && !empty($guardian->phone))) {
                $guardian->phone_hash = self::hashBlindIndex($guardian->phone);
            }
        });
    }

    /**
     * All students linked to this guardian.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardian')
            ->withPivot(['id', 'relation', 'is_primary', 'is_emergency', 'can_pickup', 'concession_percentage'])
            ->withTimestamps();
    }

    /**
     * Students where this guardian is designated as primary contact.
     */
    public function primaryStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('is_primary', true);
    }
}
