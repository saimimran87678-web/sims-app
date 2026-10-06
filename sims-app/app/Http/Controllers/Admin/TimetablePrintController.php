<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Classes;
use App\Models\PeriodConfig;
use App\Models\AcademicSession;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TimetablePrintController extends Controller
{
    /**
     * Helper to retrieve common institute branding & metadata
     */
    protected function getBrandingData($sessionId = null): array
    {
        $instituteFormalName = Setting::getGlobal('institute_formal_name');
        $instituteName = !empty($instituteFormalName) 
            ? $instituteFormalName 
            : Setting::getGlobal('institute_name', config('app.name', 'IMCB G-6/2, ISLAMABAD'));

        $logoPath = Setting::getGlobal('institute_logo', '');
        $effectiveDate = Setting::getGlobal('session_start_date', now()->format('jS M Y'));

        $session = $sessionId 
            ? AcademicSession::find($sessionId) 
            : AcademicSession::find(AcademicSession::getActiveSessionId());

        $logoBase64 = null;
        if (!empty($logoPath)) {
            $fullPath = public_path(ltrim($logoPath, '/\\'));
            if (file_exists($fullPath)) {
                $mime = mime_content_type($fullPath) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        return [
            'instituteName' => $instituteName,
            'instituteLogo' => $logoPath,
            'logoBase64'    => $logoBase64,
            'effectiveDate' => $effectiveDate,
            'session'       => $session,
        ];
    }

    /**
     * Resolve active shift type for queries
     */
    protected function resolveShiftType($session): string
    {
        $isRegular = ($session && $session->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        return ($shiftType === 'both') ? 'morning' : $shiftType;
    }

    /**
     * Helper to return either HTML view or direct DomPDF stream/download
     */
    protected function respondWithViewOrPdf(Request $request, string $viewName, array $viewData, string $filename, string $paper = 'a4', string $orientation = 'landscape')
    {
        $isPdf = ($request->query('format') === 'pdf' || $request->has('pdf') || $request->has('download'));
        $viewData['isPdf'] = $isPdf;

        if ($isPdf) {
            $pdf = Pdf::loadView($viewName, $viewData)
                ->setPaper($paper, $orientation)
                ->setOption('isRemoteEnabled', true)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('defaultFont', 'Helvetica');

            $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
            if (!str_ends_with(strtolower($safeFilename), '.pdf')) {
                $safeFilename .= '.pdf';
            }

            if ($request->has('download') || $request->query('download') == 1) {
                return $pdf->download($safeFilename);
            }

            return $pdf->stream($safeFilename);
        }

        $viewData['autoprint'] = (bool)$request->query('autoprint');
        return view($viewName, $viewData);
    }

    /**
     * 1. Master Class-Wise Timetable (Whole School A4 Landscape Matrix)
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

        $rawRows = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->leftJoin('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->leftJoin('users as teachers', 'timetables.teacher_id', '=', 'teachers.id')
            ->where('classes.academic_session_id', $sessionId)
            ->where('timetables.day', 'Monday')
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

        $classPages = $classes->chunk(9);
        if ($classPages->isEmpty()) {
            $classPages = collect([collect()]);
        }

        $viewData = array_merge($branding, [
            'periods'            => $periods,
            'assemblyPeriod'     => $assemblyPeriod,
            'breakPeriod'        => $breakPeriod,
            'nonAssemblyPeriods' => $nonAssemblyPeriods,
            'lessonPeriods'      => $lessonPeriods,
            'lessonOrdinals'     => $lessonOrdinals,
            'classes'            => $classes,
            'classPages'         => $classPages,
            'timetableGrid'      => $timetableGrid,
            'sumOfLessons'       => $sumOfLessons,
        ]);

        return $this->respondWithViewOrPdf($request, 'print.schedule.master-classwise', $viewData, 'Class-Wise-Master-Timetable.pdf', 'a4', 'landscape');
    }

    /**
     * 2. Master Teacher-Wise Timetable (Whole School A4 Landscape Matrix)
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

        $rawRows = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->leftJoin('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->where('classes.academic_session_id', $sessionId)
            ->where('timetables.day', 'Monday')
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

        $teacherPages = $teachers->chunk(9);
        if ($teacherPages->isEmpty()) {
            $teacherPages = collect([collect()]);
        }

        $viewData = array_merge($branding, [
            'periods'            => $periods,
            'assemblyPeriod'     => $assemblyPeriod,
            'breakPeriod'        => $breakPeriod,
            'nonAssemblyPeriods' => $nonAssemblyPeriods,
            'lessonPeriods'      => $lessonPeriods,
            'lessonOrdinals'     => $lessonOrdinals,
            'teachers'           => $teachers,
            'teacherPages'       => $teacherPages,
            'teacherGrid'        => $teacherGrid,
            'sumOfLessons'       => $sumOfLessons,
        ]);

        return $this->respondWithViewOrPdf($request, 'print.schedule.master-teacherwise', $viewData, 'Teacher-Wise-Master-Timetable.pdf', 'a4', 'landscape');
    }

    /**
     * 3. Individual Class Timetable ("By Class" - A4 Landscape)
     */
    public function printClass(Request $request, $id = null)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && $user->role !== 'admin') {
            abort_if(!$user->can('schedule.manage') && !$user->can('schedule.view') && !$user->can('schedule.view-sessions'), 403);
        }

        $sessionId = $request->query('session_id', AcademicSession::getActiveSessionId());

        if (!$id) {
            $class = Classes::withoutGlobalScope('active_session')->where('academic_session_id', $sessionId)->first()
                 ?? Classes::withoutGlobalScope('active_session')->first();
        } else {
            $class = Classes::withoutGlobalScope('active_session')->find($id)
                 ?? Classes::withoutGlobalScope('active_session')->where('academic_session_id', $sessionId)->first()
                 ?? Classes::withoutGlobalScope('active_session')->first();
        }

        if (!$class) {
            $class = new Classes();
            $class->id = 1;
            $class->name = 'All Classes';
            $class->academic_session_id = $sessionId;
            $class->shift_type = 'morning';
        }

        $sessionId = $class->academic_session_id ?: $sessionId;
        $branding = $this->getBrandingData($sessionId);
        $session = $branding['session'];
        $shiftType = $class->shift_type ?: $this->resolveShiftType($session);

        $classTeacher = DB::table('session_user')
            ->join('users', 'session_user.user_id', '=', 'users.id')
            ->where('session_user.academic_session_id', $sessionId)
            ->where('session_user.class_id', $class->id)
            ->select('users.name')
            ->first();

        $periods = PeriodConfig::where('shift_type', $shiftType)
            ->orderBy('period_no')
            ->get();

        $rawRows = DB::table('timetables')
            ->leftJoin('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->leftJoin('users as teachers', 'timetables.teacher_id', '=', 'teachers.id')
            ->where('timetables.class_id', $class->id)
            ->where('timetables.day', 'Monday')
            ->where('timetables.is_substitute', false)
            ->select(
                'timetables.*',
                'subjects.name as subject_name',
                'teachers.name as teacher_name'
            )
            ->get()
            ->groupBy('period_no');

        $periodRows = [];
        $totalLessons = 0;
        foreach ($periods as $p) {
            $startTime = $p->start_time ? Carbon::parse($p->start_time)->format('g:i') : '';
            $endTime = $p->end_time ? Carbon::parse($p->end_time)->format('g:i') : '';
            $duration = ($p->start_time && $p->end_time) 
                ? Carbon::parse($p->end_time)->diffInMinutes(Carbon::parse($p->start_time)) 
                : 40;

            $slots = $rawRows->get($p->period_no, collect());
            $isAssigned = $slots->isNotEmpty();
            if ($isAssigned && !$p->is_break && !$p->is_assembly) {
                $totalLessons++;
            }

            $periodRows[] = [
                'period_no'    => $p->period_no,
                'label'        => $p->label ?: ($p->is_assembly ? 'ASSEMBLY' : ($p->is_break ? 'BREAK' : "{$p->period_no} Period")),
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'time_range'   => "{$startTime} - {$endTime}",
                'duration'     => $duration,
                'is_assembly'  => (bool)$p->is_assembly,
                'is_break'     => (bool)$p->is_break,
                'slots'        => $slots,
            ];
        }

        $viewData = array_merge($branding, [
            'class'            => $class,
            'classTeacherName' => $classTeacher?->name,
            'periodRows'       => $periodRows,
            'totalLessons'     => $totalLessons,
        ]);

        return $this->respondWithViewOrPdf($request, 'print.schedule.class-timetable', $viewData, 'Class-' . str_replace(' ', '_', $class->name) . '-Timetable.pdf', 'a4', 'landscape');
    }

    /**
     * 4. Specific Teacher Timetable (Individual Slip / Diary Card)
     */
    public function printTeacherSingle(Request $request, $id = null)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && $user->role !== 'admin') {
            abort_if(!$user->can('schedule.manage') && !$user->can('schedule.view') && !$user->can('schedule.view-sessions'), 403);
        }

        $sessionId = $request->query('session_id', AcademicSession::getActiveSessionId());

        if (!$id) {
            $teacher = User::where('role', 'teacher')->first() ?? User::first();
        } else {
            $teacher = User::find($id) ?? User::where('role', 'teacher')->first() ?? User::first();
        }

        if (!$teacher) {
            $teacher = new User();
            $teacher->id = 1;
            $teacher->name = 'Faculty Member';
            $teacher->role = 'teacher';
        }

        $branding = $this->getBrandingData($sessionId);
        $session = $branding['session'];
        $shiftType = $this->resolveShiftType($session);

        $teacherData = $this->buildTeacherTimetableData($teacher, $sessionId, $shiftType);

        $viewData = array_merge($branding, [
            'teacher'     => $teacher,
            'teacherData' => $teacherData,
        ]);

        return $this->respondWithViewOrPdf($request, 'print.schedule.teacher-single', $viewData, 'Teacher-' . str_replace(' ', '_', $teacher->name) . '-Slip.pdf', 'a4', 'portrait');
    }

    /**
     * 5. Bulk Teacher Dossier (Supports all or selected subset, 6 Cards per Page)
     */
    public function printTeachersBulk(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && $user->role !== 'admin') {
            abort_if(!$user->can('schedule.manage') && !$user->can('schedule.view') && !$user->can('schedule.view-sessions'), 403);
        }

        $sessionId = $request->query('session_id', AcademicSession::getActiveSessionId());
        $branding = $this->getBrandingData($sessionId);
        $session = $branding['session'];
        $shiftType = $this->resolveShiftType($session);

        // Fetch teachers active in session
        $teachers = User::where('role', 'teacher')
            ->whereExists(function ($query) use ($sessionId, $shiftType) {
                $query->select(DB::raw(1))
                      ->from('session_user')
                      ->whereColumn('session_user.user_id', 'users.id')
                      ->where('session_user.academic_session_id', $sessionId)
                      ->where('session_user.is_active', true);
            })
            ->orderBy('name')
            ->get();

        // Check if selective teachers passed (via ?teachers=1,2,3 or ?teacher_ids=...)
        $teachersParam = $request->query('teachers') ?? $request->query('teacher_ids');
        $isSelective = false;
        if (!empty($teachersParam)) {
            $selectedIds = is_array($teachersParam) ? $teachersParam : explode(',', $teachersParam);
            $selectedIds = array_filter(array_map('intval', $selectedIds));
            if (!empty($selectedIds)) {
                $teachers = $teachers->whereIn('id', $selectedIds)->values();
                $isSelective = true;
            }
        }

        // Build data array for each teacher
        $teacherCards = [];
        foreach ($teachers as $teacher) {
            $tData = $this->buildTeacherTimetableData($teacher, $sessionId, $shiftType);
            $teacherCards[] = $tData;
        }

        // Chunk by 6 for 3x2 grid pages
        $teacherPages = array_chunk($teacherCards, 6);
        if (empty($teacherPages)) {
            $teacherPages = [[]];
        }

        $viewData = array_merge($branding, [
            'teacherPages'  => $teacherPages,
            'isSelective'   => $isSelective,
            'teachersCount' => count($teachers),
        ]);

        $filename = $isSelective 
            ? 'Selected-Teachers-Timetables-Dossier.pdf' 
            : 'All-Teachers-Dossier-Timetables.pdf';

        return $this->respondWithViewOrPdf($request, 'print.schedule.teachers-bulk', $viewData, $filename, 'a4', 'landscape');
    }

    /**
     * Shared helper to construct timetable data for a single teacher
     */
    protected function buildTeacherTimetableData($teacher, $sessionId, $shiftType): array
    {
        $periods = PeriodConfig::where('shift_type', $shiftType)
            ->orderBy('period_no')
            ->get();

        $timetables = DB::table('timetables')
            ->join('classes', 'timetables.class_id', '=', 'classes.id')
            ->leftJoin('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->where('timetables.teacher_id', $teacher->id)
            ->where('timetables.day', 'Monday')
            ->where('timetables.is_substitute', false)
            ->where('classes.academic_session_id', $sessionId)
            ->select(
                'timetables.*',
                'classes.name as class_name',
                'subjects.name as subject_name',
                'subjects.code as subject_code'
            )
            ->get()
            ->groupBy('period_no');

        $rows = [];
        $totalLessons = 0;

        foreach ($periods as $p) {
            $startTime = $p->start_time ? Carbon::parse($p->start_time)->format('g:i') : '';
            $endTime = $p->end_time ? Carbon::parse($p->end_time)->format('g:i') : '';

            $assignedSlots = $timetables->get($p->period_no);
            if ($assignedSlots && $assignedSlots->isNotEmpty() && !$p->is_break && !$p->is_assembly) {
                $totalLessons++;
            }

            $firstAssigned = $assignedSlots?->first();
            $subjectLabel = '';
            if ($assignedSlots && $assignedSlots->isNotEmpty()) {
                if ($assignedSlots->count() > 1) {
                    $abbrs = $assignedSlots->map(fn($as) => \App\Models\Subject::formatAbbreviation($as->subject_name, $as->subject_code))->unique()->filter()->values();
                    $subjectLabel = $abbrs->implode(' + ');
                } else {
                    $subjectLabel = $firstAssigned->subject_name;
                }
            }

            $rows[] = [
                'period_no'   => $p->period_no,
                'period_label'=> $p->label ?: ($p->is_assembly ? 'ASSEMBLY' : ($p->is_break ? 'BREAK' : "{$p->period_no}" . $this->ordinalSuffix($p->period_no))),
                'time_range'  => "{$startTime} - {$endTime}",
                'is_assembly' => (bool)$p->is_assembly,
                'is_break'    => (bool)$p->is_break,
                'subject'     => $subjectLabel,
                'class_name'  => $firstAssigned?->class_name,
                'room'        => $firstAssigned?->room,
            ];
        }

        return [
            'teacher'      => $teacher,
            'rows'         => $rows,
            'totalLessons' => $totalLessons,
        ];
    }

    protected function ordinalSuffix($number): string
    {
        if (in_array(($number % 100), [11, 12, 13])) {
            return 'th';
        }
        return match ($number % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }
}