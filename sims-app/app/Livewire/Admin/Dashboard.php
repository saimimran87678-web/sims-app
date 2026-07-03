<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Student;
use App\Models\Classes;
use App\Models\FeeRecord;
use App\Models\FeePayment;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public function render()
    {
        $activeSessionId = AcademicSession::getActiveSessionId();
        $activeSession   = $activeSessionId
            ? AcademicSession::find($activeSessionId)
            : null;

        // ─── Core Stats ────────────────────────────────────────────
        $classesCount = $activeSessionId
            ? Classes::where('academic_session_id', $activeSessionId)->count()
            : 0;

        $studentsCount = $activeSessionId
            ? Student::whereHas('class', fn($q) => $q->where('academic_session_id', $activeSessionId))
                     ->where('status', 'active')
                     ->count()
            : 0;

        // ─── Attendance (single query) ─────────────────────────────
        $attendanceStat = 0;
        if ($activeSessionId) {
            $row = DB::table('attendances')
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->join('classes',  'students.class_id',      '=', 'classes.id')
                ->where('classes.academic_session_id', $activeSessionId)
                ->selectRaw('COUNT(*) as total, SUM(attendances.status = "P") as present')
                ->first();

            $attendanceStat = ($row && $row->total > 0)
                ? round(($row->present / $row->total) * 100, 1)
                : 0;
        }

        // ─── Financial Overview ────────────────────────────────────
        $financials = ['generated' => 0, 'collected' => 0, 'pending' => 0, 'collection_rate' => 0];
        if ($activeSessionId) {
            $fin = FeeRecord::where('academic_session_id', $activeSessionId)
                ->selectRaw('SUM(total_amount) as generated, SUM(paid_amount) as collected, SUM(balance) as pending')
                ->first();

            $financials['generated']       = $fin->generated ?? 0;
            $financials['collected']       = $fin->collected ?? 0;
            $financials['pending']         = $fin->pending   ?? 0;
            $financials['collection_rate'] = $financials['generated'] > 0
                ? round(($financials['collected'] / $financials['generated']) * 100, 1)
                : 0;
        }

        // ─── This-Month Fee Stats ──────────────────────────────────
        $currentMonth = date('Y-m');
        $paidCount    = 0;
        if ($activeSessionId) {
            $paidCount = DB::table('fee_records')
                ->where('academic_session_id', $activeSessionId)
                ->where('period', $currentMonth)
                ->where('status', 'paid')
                ->count();
        }

        $stats = [
            'users'           => User::count(),
            'classes'         => $classesCount,
            'students'        => $studentsCount,
            'attendance'      => $attendanceStat,
            'paid_this_month' => $paidCount,
            'unpaid'          => max(0, $studentsCount - $paidCount),
        ];

        // ─── Attendance Trend (grouped single query) ───────────────
        $attendanceTrend = [];
        $chartPoints     = [];
        $svgPath         = '';
        $svgFillPath     = '';

        if ($activeSessionId) {
            $rows = DB::table('attendances')
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->join('classes',  'students.class_id',      '=', 'classes.id')
                ->where('classes.academic_session_id', $activeSessionId)
                ->groupBy('attendances.date')
                ->selectRaw('attendances.date, COUNT(*) as total, SUM(attendances.status = "P") as present')
                ->orderByDesc('attendances.date')
                ->limit(5)
                ->get()
                ->sortBy('date')
                ->values();

            foreach ($rows as $row) {
                $pct = $row->total > 0 ? round(($row->present / $row->total) * 100, 1) : 0;
                $attendanceTrend[] = [
                    'date'       => \Carbon\Carbon::parse($row->date)->format('d M'),
                    'percentage' => $pct,
                ];
            }

            if (count($attendanceTrend) > 0) {
                $totalPts = count($attendanceTrend);
                $step = 100; // Fixed spacing between points to keep chart from stretching
                // Center the group of points around X=255
                $startX = 255 - (($totalPts - 1) * $step) / 2;
                
                $pts = [];
                foreach ($attendanceTrend as $idx => $t) {
                    if ($idx >= 5) break;
                    $x = round($startX + $idx * $step);
                    // y: map 0% → y=118, 100% → y=18  (100px range, baseline 118)
                    $y = round(118 - ($t['percentage'] * 1.0));
                    $pts[] = "$x $y";
                    $chartPoints[] = ['x' => $x, 'y' => $y, 'date' => $t['date'], 'percentage' => $t['percentage']];
                }
                $firstX      = $chartPoints[0]['x'];
                $lastX       = $chartPoints[count($chartPoints) - 1]['x'];
                $svgPath     = 'M ' . implode(' L ', $pts);
                $svgFillPath = $svgPath . " L $lastX 128 L $firstX 128 Z";
            }
        }

        // ─── Activity Feed ─────────────────────────────────────────
        $activityFeed = [];
        if ($activeSessionId) {
            $payments = FeePayment::with(['student', 'record.class'])
                ->whereHas('record', fn($q) => $q->where('academic_session_id', $activeSessionId))
                ->latest()->limit(5)->get();

            foreach ($payments as $p) {
                $activityFeed[] = [
                    'type'        => 'payment',
                    'icon'        => 'check',
                    'color'       => 'emerald',
                    'title'       => ($p->student->name ?? '—'),
                    'description' => 'Rs. ' . number_format($p->amount_paid, 0) . ' via ' . $p->payment_method,
                    'meta'        => $p->record->class->name ?? '',
                    'time'        => $p->created_at,
                ];
            }

            $admissions = Student::with('class')
                ->whereHas('class', fn($q) => $q->where('academic_session_id', $activeSessionId))
                ->latest()->limit(5)->get();

            foreach ($admissions as $s) {
                $activityFeed[] = [
                    'type'        => 'admission',
                    'icon'        => 'user',
                    'color'       => 'blue',
                    'title'       => $s->name,
                    'description' => 'Admitted to ' . ($s->class->name ?? 'Unallocated'),
                    'meta'        => 'New Student',
                    'time'        => $s->created_at,
                ];
            }

            usort($activityFeed, fn($a, $b) => $b['time'] <=> $a['time']);
            $activityFeed = array_slice($activityFeed, 0, 7);
        }

        // ─── Class Distribution ────────────────────────────────────
        $classDistribution = [];
        if ($activeSessionId) {
            $classDistribution = Classes::where('academic_session_id', $activeSessionId)
                ->withCount(['students' => fn($q) => $q->where('status', 'active')])
                ->orderByDesc('students_count')
                ->limit(6)
                ->get();
        }

        // ─── Unpaid Students List (accessibility modal) ────────────
        $unpaidStudents = collect();
        if ($activeSessionId) {
            $currentMonth = date('Y-m');
            $paidStudentIds = DB::table('fee_records')
                ->where('academic_session_id', $activeSessionId)
                ->where('period', $currentMonth)
                ->where('status', 'paid')
                ->pluck('student_id');

            $unpaidStudents = Student::with('class')
                ->whereHas('class', fn($q) => $q->where('academic_session_id', $activeSessionId))
                ->where('status', 'active')
                ->whereNotIn('id', $paidStudentIds)
                ->get()
                ->groupBy(fn($student) => $student->class->name ?? 'Unallocated')
                ->map(fn($students) => $students->sortBy(fn($s) => (int) $s->roll_no))
                ->sortKeys();
        }

        return view('livewire.admin.dashboard', [
            'activeSession'     => $activeSession,
            'stats'             => $stats,
            'financials'        => $financials,
            'attendanceTrend'   => $attendanceTrend,
            'chartPoints'       => $chartPoints,
            'svgPath'           => $svgPath,
            'svgFillPath'       => $svgFillPath,
            'activityFeed'      => $activityFeed,
            'classDistribution' => $classDistribution,
            'unpaidStudents'    => $unpaidStudents,
        ])->layout('components.layouts.admin', ['title' => 'Dashboard']);
    }
}
