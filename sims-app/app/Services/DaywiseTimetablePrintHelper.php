<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class DaywiseTimetablePrintHelper
{
    /**
     * Resolve the list of working days based on the global weekend mode setting.
     */
    public static function getAllWorkingDays(): array
    {
        $weekendMode = Setting::get('weekend_mode', 'sat_sun');
        return ($weekendMode === 'sun_only')
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    }

    /**
     * Resolve the target days to print based on the request query parameter.
     */
    public static function resolveDaysToPrint(?string $requestedDay = 'all'): array
    {
        $allWorkingDays = self::getAllWorkingDays();

        if (empty($requestedDay) || strtolower($requestedDay) === 'all') {
            return $allWorkingDays;
        }

        $normalized = ucfirst(strtolower(trim($requestedDay)));
        if (in_array($normalized, $allWorkingDays, true)) {
            return [$normalized];
        }

        // Fallback to all working days if invalid day passed
        return $allWorkingDays;
    }

    /**
     * Build day packet containing timetable grid and lesson sums for classes on a specific day.
     */
    public static function buildClassDayPacket(
        int $sessionId,
        string $shiftType,
        string $day,
        $classes,
        $lessonPeriods
    ): array {
        $rawRows = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->leftJoin('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->leftJoin('users as teachers', 'timetables.teacher_id', '=', 'teachers.id')
            ->where('classes.academic_session_id', $sessionId)
            ->where('timetables.day', $day)
            ->where('timetables.is_substitute', false)
            ->when($shiftType !== 'regular', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select(
                'timetables.*',
                'classes.name as class_name',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'teachers.name as teacher_name'
            )
            ->get();

        $timetableGrid = $rawRows->groupBy(fn($r) => $r->class_id . '_' . $r->period_no);

        $sumOfLessons = [];
        foreach ($classes as $cls) {
            $count = 0;
            foreach ($lessonPeriods as $lp) {
                $slots = $timetableGrid->get($cls->id . '_' . $lp->period_no);
                if ($slots && $slots->isNotEmpty()) {
                    $count++;
                }
            }
            $sumOfLessons[$cls->id] = $count;
        }

        return [
            'day' => $day,
            'timetableGrid' => $timetableGrid,
            'sumOfLessons' => $sumOfLessons,
        ];
    }

    /**
     * Build day packet containing timetable grid and lesson sums for teachers on a specific day.
     */
    public static function buildTeacherDayPacket(
        int $sessionId,
        string $shiftType,
        string $day,
        $teachers,
        $lessonPeriods
    ): array {
        $rawRows = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->leftJoin('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->where('classes.academic_session_id', $sessionId)
            ->where('timetables.day', $day)
            ->where('timetables.is_substitute', false)
            ->whereNotNull('timetables.teacher_id')
            ->select(
                'timetables.*',
                'classes.name as class_name',
                'subjects.name as subject_name',
                'subjects.code as subject_code'
            )
            ->get();

        $teacherGrid = $rawRows->groupBy(fn($r) => $r->teacher_id . '_' . $r->period_no);

        $sumOfLessons = [];
        foreach ($teachers as $t) {
            $count = 0;
            foreach ($lessonPeriods as $lp) {
                $slots = $teacherGrid->get($t->id . '_' . $lp->period_no);
                if ($slots && $slots->isNotEmpty()) {
                    $count++;
                }
            }
            $sumOfLessons[$t->id] = $count;
        }

        return [
            'day' => $day,
            'teacherGrid' => $teacherGrid,
            'sumOfLessons' => $sumOfLessons,
        ];
    }
}
