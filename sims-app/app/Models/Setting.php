<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{

    /**
     * In-memory cache to prevent redundant database queries during a single request cycle.
     */
    protected static array $runtimeCache = [];

    protected $fillable = [
        'key',
        'value',
        'academic_session_id',
    ];

    /**
     * Clear in-memory runtime cache.
     */
    public static function clearRuntimeCache(): void
    {
        self::$runtimeCache = [];
    }

    /**
     * Get a setting value by key, scoped to the active academic session.
     */
    public static function get(string $key, $default = null)
    {
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        $cacheKey = "s_{$sessionId}_{$key}";
        if (array_key_exists($cacheKey, self::$runtimeCache)) {
            return self::$runtimeCache[$cacheKey] ?? $default;
        }

        try {
            $setting = self::where('key', $key)->where('academic_session_id', $sessionId)->first();
            
            // Fallback to global setting if session-specific is not found
            if (!$setting) {
                $setting = self::where('key', $key)->whereNull('academic_session_id')->first();
            }

            $val = $setting ? $setting->value : null;
            self::$runtimeCache[$cacheKey] = $val;
            return $val ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set a setting value for the active academic session.
     */
    public static function set(string $key, $value)
    {
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        $cacheKey = "s_{$sessionId}_{$key}";
        self::$runtimeCache[$cacheKey] = $value;

        return self::updateOrCreate(
            ['key' => $key, 'academic_session_id' => $sessionId],
            ['value' => $value]
        );
    }

    /**
     * Get a global setting (not scoped to any academic session).
     * Used for system-level settings like WhatsApp queue configuration
     * that must be accessible from CLI commands and background daemons.
     */
    public static function getGlobal(string $key, $default = null)
    {
        $cacheKey = "global_{$key}";
        if (array_key_exists($cacheKey, self::$runtimeCache)) {
            return self::$runtimeCache[$cacheKey] ?? $default;
        }

        try {
            $setting = self::where('key', $key)->whereNull('academic_session_id')->first();
            $val = $setting ? $setting->value : null;
            self::$runtimeCache[$cacheKey] = $val;
            return $val ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set a global setting (not scoped to any academic session).
     */
    public static function setGlobal(string $key, $value)
    {
        $cacheKey = "global_{$key}";
        self::$runtimeCache[$cacheKey] = $value;

        return self::updateOrCreate(
            ['key' => $key, 'academic_session_id' => null],
            ['value' => $value]
        );
    }
}
