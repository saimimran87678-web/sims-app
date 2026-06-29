<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{

    protected $fillable = [
        'key',
        'value',
        'academic_session_id',
    ];

    /**
     * Get a setting value by key, scoped to the active academic session.
     */
    public static function get(string $key, $default = null)
    {
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        
        $setting = self::where('key', $key)->where('academic_session_id', $sessionId)->first();
        
        // Fallback to global setting if session-specific is not found
        if (!$setting) {
            $setting = self::where('key', $key)->whereNull('academic_session_id')->first();
        }

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value for the active academic session.
     */
    public static function set(string $key, $value)
    {
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();

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
        $setting = self::where('key', $key)->whereNull('academic_session_id')->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a global setting (not scoped to any academic session).
     */
    public static function setGlobal(string $key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key, 'academic_session_id' => null],
            ['value' => $value]
        );
    }
}
