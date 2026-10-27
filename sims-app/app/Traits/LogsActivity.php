<?php

namespace App\Traits;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            if (!static::shouldLogActivity('created')) {
                return;
            }

            $label = ActivityLogger::resolveSubjectLabel($model);
            $newValues = static::filterLoggedAttributes($model->getAttributes());

            ActivityLogger::log(
                action: 'created',
                description: "Created {$label}",
                model: $model,
                oldValues: null,
                newValues: $newValues
            );
        });

        static::updated(function (Model $model) {
            if (!static::shouldLogActivity('updated')) {
                return;
            }

            $rawChanges = $model->getChanges();
            // Ignore if only updated_at changed
            unset($rawChanges['updated_at']);

            if (empty($rawChanges)) {
                return;
            }

            $oldValues = [];
            $newValues = [];

            foreach (array_keys($rawChanges) as $key) {
                if (in_array($key, static::getIgnoredActivityAttributes(), true)) {
                    continue;
                }
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $model->getAttribute($key);
            }

            if (empty($newValues)) {
                return;
            }

            $label = ActivityLogger::resolveSubjectLabel($model);
            $changedKeys = implode(', ', array_keys($newValues));

            ActivityLogger::log(
                action: 'updated',
                description: "Updated {$label} ({$changedKeys})",
                model: $model,
                oldValues: $oldValues,
                newValues: $newValues
            );
        });

        static::deleted(function (Model $model) {
            if (!static::shouldLogActivity('deleted')) {
                return;
            }

            $label = ActivityLogger::resolveSubjectLabel($model);
            $oldValues = static::filterLoggedAttributes($model->getOriginal());

            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted {$label}",
                model: $model,
                oldValues: $oldValues,
                newValues: null
            );
        });
    }

    /**
     * Determine if action should be logged
     */
    protected static function shouldLogActivity(string $action): bool
    {
        return true;
    }

    /**
     * Attributes to ignore during automated logging
     */
    protected static function getIgnoredActivityAttributes(): array
    {
        return [
            'updated_at',
            'created_at',
            'remember_token',
            'two_factor_recovery_codes',
            'two_factor_secret',
        ];
    }

    /**
     * Filter attributes to remove ignored keys
     */
    protected static function filterLoggedAttributes(array $attributes): array
    {
        $ignored = static::getIgnoredActivityAttributes();
        foreach ($ignored as $key) {
            unset($attributes[$key]);
        }
        return $attributes;
    }
}
