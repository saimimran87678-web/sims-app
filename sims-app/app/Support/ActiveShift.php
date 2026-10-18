<?php

namespace App\Support;

use App\Models\AcademicSession;

/**
 * Which shift the signed-in user is working in. One place for the rule that
 * used to be copied into every screen: a Regular session always means
 * 'regular'; otherwise the shift picked in the shift switcher (morning,
 * evening, or 'both'), defaulting to morning.
 *
 * Callers ask for the answer in the form they need:
 *  - current()           the raw answer, may be 'both'
 *  - single()            one concrete shift, for writes and single-shift screens
 *  - filter()            a shift to filter by, or null meaning "all shifts"
 *  - permissionShifts()  the shift values to match in permission rows
 */
class ActiveShift
{
    public const SESSION_KEY = 'selected_shift_type';

    public static function isRegularSession(?int $sessionId = null): bool
    {
        $sessionId ??= AcademicSession::getActiveSessionId();
        $session = $sessionId ? AcademicSession::find($sessionId) : null;

        return (bool) ($session && $session->shift_type === 'Regular');
    }

    /** 'regular', 'morning', 'evening' or 'both'. */
    public static function current(?int $sessionId = null): string
    {
        if (self::isRegularSession($sessionId)) {
            return 'regular';
        }

        // A 'regular' left over from logging in under a Regular session means nothing once the
        // session has become Dual shift: the user is then in the morning shift until they pick one.
        $shift = session(self::SESSION_KEY, 'morning');

        return $shift === 'regular' ? 'morning' : $shift;
    }

    /** 'regular', 'morning' or 'evening'; 'both' becomes 'morning'. */
    public static function single(?int $sessionId = null): string
    {
        $shift = self::current($sessionId);

        return $shift === 'both' ? 'morning' : $shift;
    }

    /** The shift to filter by, or null when the user sees both shifts. */
    public static function filter(?int $sessionId = null): ?string
    {
        $shift = self::current($sessionId);

        return $shift === 'both' ? null : $shift;
    }

    /** @return list<string> */
    public static function permissionShifts(?int $sessionId = null): array
    {
        $shift = self::current($sessionId);

        return $shift === 'both' ? ['morning', 'evening', 'both'] : [$shift];
    }
}
