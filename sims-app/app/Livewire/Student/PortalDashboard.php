<?php

namespace App\Livewire\Student;

use Livewire\Component;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Timetable;
use App\Models\Attendance;
use App\Models\FeeRecord;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\DatesheetSchedule;
use App\Models\AcademicSession;
use App\Models\Setting;
use App\Models\Substitution;
use App\Services\LicenseStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PortalDashboard extends Component
{
    public $studentId;
    public $activeTab = 'overview'; // 'overview', 'timetable', 'attendance', 'results', 'fees'
    public $selectedDay;
    public $whatsappOptIn = true;

    public function getSchoolDays(): array
    {
        $weekendMode = Setting::get('weekend_mode', 'sat_sun');
        $includeSaturday = Setting::get('include_saturday_in_schedule', 'no') === 'yes';

        if ($weekendMode === 'sun_only' || $includeSaturday) {
            return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        }

        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    }

    public function mount()
    {
        $sessionStudentId = session('student_portal_id');

        if (! $sessionStudentId) {
            return redirect()->route('student.login');
        }

        $this->studentId = $sessionStudentId;
        $schoolDays = $this->getSchoolDays();
        $todayDay = strtolower(Carbon::now()->format('l'));

        // If today is a school day, select it; otherwise default to the first school day (Monday)
        if (in_array($todayDay, $schoolDays)) {
            $this->selectedDay = $todayDay;
        } else {
            $this->selectedDay = $schoolDays[0] ?? 'monday';
        }

        $this->loadGuardianPreferences();
    }

    protected function loadGuardianPreferences(): void
    {
        $student = Student::find($this->studentId);
        $this->whatsappOptIn = (bool) ($student?->primaryGuardian?->whatsapp_opt_in ?? true);
    }

    public function toggleWhatsAppOptIn(): void
    {
        $student = Student::find($this->studentId);
        if (! $student) return;

        $guardian = $student->primaryGuardian;
        if ($guardian) {
            $guardian->whatsapp_opt_in = ! $guardian->whatsapp_opt_in;
            $guardian->save();
            $this->whatsappOptIn = (bool) $guardian->whatsapp_opt_in;
            session()->flash('whatsapp_message', $guardian->whatsapp_opt_in
                ? 'WhatsApp notification alerts enabled.'
                : 'WhatsApp notification alerts muted by parent request.');
        }
    }

    public function switchChild($childId)
    {
        $currentStudent = Student::find($this->studentId);
        if (! $currentStudent) {
            return redirect()->route('student.login');
        }

        // Verify child is authorized under the same family CNIC
        $familyUnit = $currentStudent->family_unit;
        $targetChild = $familyUnit->firstWhere('id', (int) $childId);

        if ($targetChild) {
            $this->studentId = $targetChild->id;
            session([
                'student_portal_id' => $targetChild->id,
                'student_portal_name' => $targetChild->name,
            ]);
            $this->loadGuardianPreferences();
        }
    }

    public function setTab($tab)
    {
        if (in_array($tab, ['overview', 'timetable', 'attendance', 'results', 'fees'])) {
            $this->activeTab = $tab;
        }
    }

    public function selectDay($day)
    {
        $day = strtolower($day);
        if (in_array($day, $this->getSchoolDays())) {
            $this->selectedDay = $day;
        }
    }

    public function render()
    {
        $student = Student::with(['class', 'subjects'])->find($this->studentId);

        if (! $student) {
            session()->forget(['student_portal_id', 'student_portal_cnic', 'student_portal_name']);
            return redirect()->route('student.login');
        }

        $familySiblings = $student->family_unit;
        $activeSessionId = AcademicSession::getActiveSessionId();
        $classId = $student->class_id;

        // Class Teacher Lookup
        $classTeacher = null;
        if ($classId) {
            $classTeacher = DB::table('session_user')
                ->join('users', 'session_user.user_id', '=', 'users.id')
                ->where('session_user.class_id', $classId)
                ->select('users.name')
                ->first();
        }
        $classTeacherName = $classTeacher ? $classTeacher->name : null;

        // 1. Attendance Metrics (Today's Scan & Monthly Stats)
        $todayStr = Carbon::today()->toDateString();
        $todayAttendance = Attendance::where('student_id', $student->id)
            ->where('date', $todayStr)
            ->first();

        if ($todayAttendance) {
            $todaySt = strtoupper($todayAttendance->status);
            $todayAttendance->normalized_status = match ($todaySt) {
                'P', 'PRESENT' => 'present',
                'A', 'ABSENT' => 'absent',
                'L', 'LEAVE' => 'leave',
                'LT', 'LATE' => 'late',
                default => strtolower($todayAttendance->status),
            };
            $todayAttendance->status_label = match ($todayAttendance->normalized_status) {
                'present' => 'Present',
                'absent' => 'Absent',
                'leave' => 'Leave',
                'late' => 'Late',
                default => ucfirst($todayAttendance->status),
            };
        }

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        $monthlyAttendances = Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($att) use ($classTeacherName) {
                $st = strtoupper($att->status);
                $normalized = match ($st) {
                    'P', 'PRESENT' => 'present',
                    'A', 'ABSENT' => 'absent',
                    'L', 'LEAVE' => 'leave',
                    'LT', 'LATE' => 'late',
                    default => strtolower($att->status),
                };

                $label = match ($normalized) {
                    'present' => 'Present',
                    'absent' => 'Absent',
                    'leave' => 'Leave',
                    'late' => 'Late',
                    default => ucfirst($att->status),
                };

                $att->normalized_status = $normalized;
                $att->status_label = $label;
                $att->source = $classTeacherName 
                    ? "Class Teacher ({$classTeacherName})" 
                    : "Class Teacher";

                return $att;
            });

        $totalDays = $monthlyAttendances->count();
        $presentDays = $monthlyAttendances->filter(fn($a) => $a->normalized_status === 'present')->count();
        $lateDays = $monthlyAttendances->filter(fn($a) => $a->normalized_status === 'late')->count();
        $absentDays = $monthlyAttendances->filter(fn($a) => $a->normalized_status === 'absent')->count();
        $leaveDays = $monthlyAttendances->filter(fn($a) => $a->normalized_status === 'leave')->count();
        $attendanceRate = $totalDays > 0 ? round((($presentDays + ($lateDays * 0.5)) / $totalDays) * 100, 1) : 100.0;

        // 2. Timetable Data (with Universal Routine Support & PeriodConfig Timings)
        $weeklyTimetable = collect();
        $isUniversalSchedule = (LicenseStatus::getEffectiveScheduleType() === 'single_schedule');

        if ($classId) {
            $shiftType = session('selected_shift_type', $student->class?->shift_type ?? 'morning');
            if ($shiftType === 'both') {
                $shiftType = 'morning';
            }

            $periodConfigs = \App\Models\PeriodConfig::where(function ($q) use ($shiftType) {
                    $q->where('shift_type', $shiftType)
                      ->orWhere('shift_type', 'regular');
                })
                ->orderBy('period_no')
                ->get()
                ->keyBy('period_no');

            $baseTimetableQuery = Timetable::where('class_id', $classId)
                ->with(['subject', 'teacher'])
                ->orderBy('period_no');

            if ($isUniversalSchedule) {
                // In Universal Routine Mode, Monday is the single master routine across all active school days
                $universalRoutine = (clone $baseTimetableQuery)
                    ->where('day', 'Monday')
                    ->get();

                // If Monday has no records, fallback to first available day
                if ($universalRoutine->isEmpty()) {
                    $firstDay = (clone $baseTimetableQuery)->value('day');
                    if ($firstDay) {
                        $universalRoutine = (clone $baseTimetableQuery)->where('day', $firstDay)->get();
                    }
                }

                $universalRoutine->each(function ($item) use ($periodConfigs) {
                    if (! $item->start_time && isset($periodConfigs[$item->period_no])) {
                        $item->start_time = $periodConfigs[$item->period_no]->start_time;
                        $item->end_time = $periodConfigs[$item->period_no]->end_time;
                    }
                });

                // Replicate universal routine across all configured school days
                $weeklyTimetable = collect();
                foreach ($this->getSchoolDays() as $sDay) {
                    $weeklyTimetable->put($sDay, $universalRoutine);
                }
            } else {
                // Day-Wise Schedule Mode
                $weeklyTimetable = $baseTimetableQuery->get()
                    ->each(function ($item) use ($periodConfigs) {
                        if (! $item->start_time && isset($periodConfigs[$item->period_no])) {
                            $item->start_time = $periodConfigs[$item->period_no]->start_time;
                            $item->end_time = $periodConfigs[$item->period_no]->end_time;
                        }
                    })
                    ->groupBy(function ($item) {
                        return strtolower(trim($item->day));
                    });
            }
        }

        // Today's classes for selectedDay (case-insensitive lookup)
        $todayClasses = $weeklyTimetable->get(strtolower($this->selectedDay), collect());

        // Check if there are active substitutions for today for this class
        $todayDayName = strtolower(now()->format('l'));
        if (strtolower($this->selectedDay) === $todayDayName && $classId) {
            $todaySubstitutions = Substitution::where('class_id', $classId)
                ->where('date', $todayStr)
                ->where('status', '!=', 'cancelled')
                ->with(['substituteTeacher', 'subject'])
                ->get()
                ->keyBy('period_no');

            if ($todaySubstitutions->isNotEmpty()) {
                $todayClasses = $todayClasses->map(function ($cls) use ($todaySubstitutions) {
                    $item = clone $cls;
                    if (isset($todaySubstitutions[$item->period_no])) {
                        $sub = $todaySubstitutions[$item->period_no];
                        $item->teacher = $sub->substituteTeacher;
                        $item->is_substitute_today = true;
                        $item->substitute_note = "Substitute: " . ($sub->substituteTeacher->name ?? 'Assigned');
                    }
                    return $item;
                });
            }
        }

        // 3. Fee Ledgers & Invoices
        $feeRecords = FeeRecord::where('student_id', $student->id)
            ->orderBy('due_date', 'desc')
            ->get();

        $pendingFee = $feeRecords->whereIn('status', ['unpaid', 'partial'])->sum('balance');
        $latestInvoice = $feeRecords->first();

        // 4. Exam Results & Upcoming Datesheet - Strictly Gated to Published Exams
        $examMarks = DB::table('exam_marks')
            ->join('exams', 'exam_marks.exam_id', '=', 'exams.id')
            ->join('subjects', 'exam_marks.subject_id', '=', 'subjects.id')
            ->where('exam_marks.student_id', $student->id)
            ->where('exams.lifecycle_status', \App\Models\Exam::STATUS_PUBLISHED)
            ->select(
                'exams.name as exam_name',
                'exams.id as exam_id',
                'exams.published_at',
                'subjects.name as subject_name',
                'exam_marks.marks_obtained',
                'exam_marks.max_marks',
                'exam_marks.is_absent'
            )
            ->orderBy('exams.id', 'desc')
            ->get()
            ->groupBy('exam_name');

        // Upcoming/Published exam datesheets for this student's class
        $datesheets = collect();
        if ($classId) {
            // Source 1: Schedules created in Exam Hub / Matrix Builder (exam_schedules)
            $examSchedules = ExamSchedule::where('class_id', $classId)
                ->with(['exam', 'subject'])
                ->whereNotNull('exam_date')
                ->where('exam_date', '>=', $todayStr)
                ->orderBy('exam_date', 'asc')
                ->get()
                ->map(function ($es) {
                    return (object) [
                        'id' => 'es_' . $es->id,
                        'exam_name' => $es->exam->name ?? 'Exam',
                        'subject_name' => $es->subject->name ?? 'Subject',
                        'exam_date' => $es->exam_date,
                        'start_time' => $es->start_time ? substr($es->start_time, 0, 5) : '09:00',
                        'end_time' => $es->end_time ? substr($es->end_time, 0, 5) : null,
                    ];
                });

            // Source 2: Schedules created in Dedicated Datesheet Builder (datesheet_schedules)
            $datesheetSchedules = DatesheetSchedule::where('class_id', $classId)
                ->with('exam')
                ->whereNotNull('exam_date')
                ->where('exam_date', '>=', $todayStr)
                ->orderBy('exam_date', 'asc')
                ->get()
                ->map(function ($ds) {
                    return (object) [
                        'id' => 'ds_' . $ds->id,
                        'exam_name' => $ds->exam->name ?? 'Exam',
                        'subject_name' => $ds->subject ?? 'Subject',
                        'exam_date' => $ds->exam_date,
                        'start_time' => $ds->start_time ? substr($ds->start_time, 0, 5) : '09:00',
                        'end_time' => $ds->end_time ? substr($ds->end_time, 0, 5) : null,
                    ];
                });

            $combined = $examSchedules->concat($datesheetSchedules);

            // If no future datesheets, fallback to showing all published papers for this class
            if ($combined->isEmpty()) {
                $pastEs = ExamSchedule::where('class_id', $classId)
                    ->with(['exam', 'subject'])
                    ->whereNotNull('exam_date')
                    ->orderBy('exam_date', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($es) {
                        return (object) [
                            'id' => 'es_' . $es->id,
                            'exam_name' => $es->exam->name ?? 'Exam',
                            'subject_name' => $es->subject->name ?? 'Subject',
                            'exam_date' => $es->exam_date,
                            'start_time' => $es->start_time ? substr($es->start_time, 0, 5) : '09:00',
                            'end_time' => $es->end_time ? substr($es->end_time, 0, 5) : null,
                        ];
                    });

                $pastDs = DatesheetSchedule::where('class_id', $classId)
                    ->with('exam')
                    ->whereNotNull('exam_date')
                    ->orderBy('exam_date', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($ds) {
                        return (object) [
                            'id' => 'ds_' . $ds->id,
                            'exam_name' => $ds->exam->name ?? 'Exam',
                            'subject_name' => $ds->subject ?? 'Subject',
                            'exam_date' => $ds->exam_date,
                            'start_time' => $ds->start_time ? substr($ds->start_time, 0, 5) : '09:00',
                            'end_time' => $ds->end_time ? substr($ds->end_time, 0, 5) : null,
                        ];
                    });

                $combined = $pastEs->concat($pastDs)->sortBy('exam_date')->values();
            } else {
                $combined = $combined->sortBy('exam_date')->values();
            }

            $datesheets = $combined->take(12);
        }

        return view('livewire.student.portal-dashboard', [
            'student' => $student,
            'familySiblings' => $familySiblings,
            'todayAttendance' => $todayAttendance,
            'attendanceRate' => $attendanceRate,
            'monthlyAttendances' => $monthlyAttendances,
            'totalDays' => $totalDays,
            'presentDays' => $presentDays,
            'lateDays' => $lateDays,
            'absentDays' => $absentDays,
            'leaveDays' => $leaveDays,
            'weeklyTimetable' => $weeklyTimetable,
            'todayClasses' => $todayClasses,
            'feeRecords' => $feeRecords,
            'pendingFee' => $pendingFee,
            'latestInvoice' => $latestInvoice,
            'examMarks' => $examMarks,
            'datesheets' => $datesheets,
            'activeTab' => $this->activeTab,
            'selectedDay' => $this->selectedDay,
            'classTeacherName' => $classTeacherName,
            'schoolDays' => $this->getSchoolDays(),
            'isUniversalSchedule' => $isUniversalSchedule,
        ])->layout('layouts.student-portal', ['title' => "Student Portal — {$student->name}"]);
    }
}
