<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Classes;
use App\Models\PeriodConfig;
use App\Models\AcademicSession;
use App\Models\User;
use App\Services\DaywiseTimetablePrintHelper;

class DaywiseTimetablePrintController extends TimetablePrintController
{
    /**
     * Day-Wise Master Class-Wise Timetable Print (A4 Landscape Matrix per day)
     */
    public function printMasterClasswise(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && $user->role !== 'admin') {
            abort_if(!$user->can('schedule.manage') && !$user->can('schedule.view') && !$user->can('schedule.view-sessions'), 403);
        }

        $sessionId = $request->query('session_id', AcademicSession::getActiveSessionId());
        $branding = $this->getBrandingData($sessionId);
        $session = $branding['session'];
        $shiftType = $this->resolveShiftType($session);

        $periods = PeriodConfig::where('shift_type', $shiftType)
            ->orderBy('period_no')
            ->get();

        $assemblyPeriod = $periods->first(fn($p) => $p->is_assembly || str_contains(strtolower($p->label ?? ''), 'assembly'));
        $breakPeriod = $periods->first(fn($p) => $p->is_break || str_contains(strtolower($p->label ?? ''), 'break'));
        $nonAssemblyPeriods = $periods->filter(fn($p) => !$p->is_assembly && !str_contains(strtolower($p->label ?? ''), 'assembly'))->values();
        $lessonPeriods = $periods->filter(fn($p) => (!$p->is_break && !$p->is_assembly && !str_contains(strtolower($p->label ?? ''), 'assembly') && !str_contains(strtolower($p->label ?? ''), 'break')))->values();

        $lessonOrdinals = [];
        $lessonCounter = 1;
        foreach ($periods as $p) {
            if (!$p->is_assembly && !$p->is_break && !str_contains(strtolower($p->label ?? ''), 'assembly') && !str_contains(strtolower($p->label ?? ''), 'break')) {
                $num = $lessonCounter++;
                $suffix = match($num % 10) {
                    1 => ($num % 100 == 11 ? 'th' : 'st'),
                    2 => ($num % 100 == 12 ? 'th' : 'nd'),
                    3 => ($num % 100 == 13 ? 'th' : 'rd'),
                    default => 'th',
                };
                $lessonOrdinals[$p->period_no] = $num . $suffix;
            }
        }

        $classes = Classes::withoutGlobalScope('active_session')
            ->leftJoin('session_user', function ($join) use ($sessionId) {
                $join->on('classes.id', '=', 'session_user.class_id')
                     ->where('session_user.academic_session_id', '=', $sessionId);
            })
            ->leftJoin('users', 'session_user.user_id', '=', 'users.id')
            ->where('classes.academic_session_id', $sessionId)
            ->when($shiftType !== 'regular', function ($q) use ($shiftType) {
                $q->where('classes.shift_type', $shiftType);
            })
            ->select('classes.*', 'users.name as class_teacher_name')
            ->orderBy('classes.numeric_value')
            ->orderBy('classes.name')
            ->get();

        $classPages = $classes->chunk(9);
        if ($classPages->isEmpty()) {
            $classPages = collect([collect()]);
        }

        $requestedDay = $request->query('day', 'all');
        $targetDays = DaywiseTimetablePrintHelper::resolveDaysToPrint($requestedDay);

        $daysData = [];
        foreach ($targetDays as $day) {
            $packet = DaywiseTimetablePrintHelper::buildClassDayPacket(
                $sessionId,
                $shiftType,
                $day,
                $classes,
                $lessonPeriods
            );
            $daysData[] = $packet;
        }

        $isSingleDay = (count($targetDays) === 1);
        $dayLabel = $isSingleDay ? $targetDays[0] : 'All-Days';
        $filename = "Daywise-Class-Master-Timetable-{$dayLabel}.pdf";

        $viewData = array_merge($branding, [
            'periods'            => $periods,
            'assemblyPeriod'     => $assemblyPeriod,
            'breakPeriod'        => $breakPeriod,
            'nonAssemblyPeriods' => $nonAssemblyPeriods,
            'lessonPeriods'      => $lessonPeriods,
            'lessonOrdinals'     => $lessonOrdinals,
            'classes'            => $classes,
            'classPages'         => $classPages,
            'daysData'           => $daysData,
            'targetDays'         => $targetDays,
            'isSingleDay'        => $isSingleDay,
            'selectedDay'        => $requestedDay,
        ]);

        return $this->respondWithViewOrPdf($request, 'print.schedule.daywise-master-classwise', $viewData, $filename, 'a4', 'landscape');
    }

    /**
     * Day-Wise Master Teacher-Wise Timetable Print (A4 Landscape Matrix per day)
     */
    public function printMasterTeacherwise(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && $user->role !== 'admin') {
            abort_if(!$user->can('schedule.manage') && !$user->can('schedule.view') && !$user->can('schedule.view-sessions'), 403);
        }

        $sessionId = $request->query('session_id', AcademicSession::getActiveSessionId());
        $branding = $this->getBrandingData($sessionId);
        $session = $branding['session'];
        $shiftType = $this->resolveShiftType($session);

        $periods = PeriodConfig::where('shift_type', $shiftType)
            ->orderBy('period_no')
            ->get();

        $assemblyPeriod = $periods->first(fn($p) => $p->is_assembly || str_contains(strtolower($p->label ?? ''), 'assembly'));
        $breakPeriod = $periods->first(fn($p) => $p->is_break || str_contains(strtolower($p->label ?? ''), 'break'));
        $nonAssemblyPeriods = $periods->filter(fn($p) => !$p->is_assembly && !str_contains(strtolower($p->label ?? ''), 'assembly'))->values();
        $lessonPeriods = $periods->filter(fn($p) => (!$p->is_break && !$p->is_assembly && !str_contains(strtolower($p->label ?? ''), 'assembly') && !str_contains(strtolower($p->label ?? ''), 'break')))->values();

        $lessonOrdinals = [];
        $lessonCounter = 1;
        foreach ($periods as $p) {
            if (!$p->is_assembly && !$p->is_break && !str_contains(strtolower($p->label ?? ''), 'assembly') && !str_contains(strtolower($p->label ?? ''), 'break')) {
                $num = $lessonCounter++;
                $suffix = match($num % 10) {
                    1 => ($num % 100 == 11 ? 'th' : 'st'),
                    2 => ($num % 100 == 12 ? 'th' : 'nd'),
                    3 => ($num % 100 == 13 ? 'th' : 'rd'),
                    default => 'th',
                };
                $lessonOrdinals[$p->period_no] = $num . $suffix;
            }
        }

        $teachers = User::where('role', 'teacher')
            ->whereExists(function ($query) use ($sessionId, $shiftType) {
                $query->select(DB::raw(1))
                      ->from('session_user')
                      ->whereColumn('session_user.user_id', 'users.id')
                      ->where('session_user.academic_session_id', $sessionId)
                      ->where('session_user.is_active', true)
                      ->when($shiftType !== 'regular', function ($q) use ($shiftType) {
                          $q->where(function ($sq) use ($shiftType) {
                              $sq->where('session_user.allowed_shifts', 'both')
                                 ->orWhere('session_user.allowed_shifts', $shiftType);
                          });
                      });
            })
            ->orderBy('name')
            ->get();

        $teacherPages = $teachers->chunk(9);
        if ($teacherPages->isEmpty()) {
            $teacherPages = collect([collect()]);
        }

        $requestedDay = $request->query('day', 'all');
        $targetDays = DaywiseTimetablePrintHelper::resolveDaysToPrint($requestedDay);

        $daysData = [];
        foreach ($targetDays as $day) {
            $packet = DaywiseTimetablePrintHelper::buildTeacherDayPacket(
                $sessionId,
                $shiftType,
                $day,
                $teachers,
                $lessonPeriods
            );
            $daysData[] = $packet;
        }

        $isSingleDay = (count($targetDays) === 1);
        $dayLabel = $isSingleDay ? $targetDays[0] : 'All-Days';
        $filename = "Daywise-Teacher-Master-Timetable-{$dayLabel}.pdf";

        $viewData = array_merge($branding, [
            'periods'            => $periods,
            'assemblyPeriod'     => $assemblyPeriod,
            'breakPeriod'        => $breakPeriod,
            'nonAssemblyPeriods' => $nonAssemblyPeriods,
            'lessonPeriods'      => $lessonPeriods,
            'lessonOrdinals'     => $lessonOrdinals,
            'teachers'           => $teachers,
            'teacherPages'       => $teacherPages,
            'daysData'           => $daysData,
            'targetDays'         => $targetDays,
            'isSingleDay'        => $isSingleDay,
            'selectedDay'        => $requestedDay,
        ]);

        return $this->respondWithViewOrPdf($request, 'print.schedule.daywise-master-teacherwise', $viewData, $filename, 'a4', 'landscape');
    }
}
