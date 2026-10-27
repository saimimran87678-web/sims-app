<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'module',
        'model_type',
        'model_id',
        'subject_label',
        'description',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'academic_session_id',
        'shift_type',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    public function getActionColorAttribute(): string
    {
        return match (strtolower((string) $this->action)) {
            'created' => 'emerald',
            'saved' => 'teal',
            'updated' => 'amber',
            'deleted' => 'rose',
            'override', 'admin_override' => 'orange',
            'printed' => 'indigo',
            'login', 'logout' => 'blue',
            'password_change', 'security' => 'purple',
            'restored' => 'teal',
            default => 'slate',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match (strtolower((string) $this->action)) {
            'created' => 'Created',
            'saved' => 'Marked / Saved',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'override', 'admin_override' => 'Admin Override',
            'printed' => 'Printed / Exported',
            'login' => 'User Login',
            'logout' => 'User Logout',
            'password_change' => 'Password Changed',
            'restored' => 'Restored',
            default => ucfirst((string) $this->action),
        };
    }

    public function getFormattedDiffAttribute(): array
    {
        $diff = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $fields = $this->changed_fields ?? array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($fields as $field) {
            $diff[$field] = [
                'old' => $old[$field] ?? null,
                'new' => $new[$field] ?? null,
            ];
        }

        return $diff;
    }
}
