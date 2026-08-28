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
        $activeSession = $activeSessionId ? AcademicSession::find($activeSessionId) : null;
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        // ─── Core Stats ────────────────────────────────────────────
        $classesCount = $activeSessionId
            ? Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $activeSessionId)
                ->when($shiftType !== 'both', fn($q) => $q->where('shift_type', $shiftType))
                ->count()
            : 0;

        $studentsCount = $activeSessionId
            ? Student::whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
                    $q->where('academic_session_id', $activeSessionId)->active();
                    if ($shiftType !== 'both') {
                        $q->where('shift_type', $shiftType);
                    }
              })
              ->count()
            : 0;

        // ─── Attendance (single query) ─────────────────────────────
        $attendanceStat = 0;
        if ($activeSessionId && $activeSession) {
            $rowQuery = DB::table('attendances')
                ->join('enrollments', 'attendances.student_id', '=', 'enrollments.student_id')
                ->where('attendances.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active')
                ->whereBetween('attendances.date', [$activeSession->start_date, $activeSession->end_date]);

            if ($shiftType !== 'both') {
                $rowQuery->where('enrollments.shift_type', $shiftType);
            }

            $row = $rowQuery->selectRaw('COUNT(*) as total, SUM(attendances.status = "P") as present')
                ->first();

            $attendanceStat = ($row && $row->total > 0)
                ? round(($row->present / $row->total) * 100, 1)
                : 0;
        }

        // ─── Financial Overview ────────────────────────────────────
        $financials = ['generated' => 0, 'collected' => 0, 'pending' => 0, 'collection_rate' => 0];
        if ($activeSessionId) {
            $finQuery = FeeRecord::where('fee_records.academic_session_id', $activeSessionId)
                ->join('enrollments', 'fee_records.student_id', '=', 'enrollments.student_id')
                ->where('enrollments.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active');

            if ($shiftType !== 'both') {
                $finQuery->where('enrollments.shift_type', $shiftType);
            }

            $fin = $finQuery->selectRaw('SUM(fee_records.total_amount) as generated, SUM(fee_records.paid_amount) as collected, SUM(fee_records.balance) as pending')
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
            $paidQuery = DB::table('fee_records')
                ->join('enrollments', 'fee_records.student_id', '=', 'enrollments.student_id')
                ->where('fee_records.academic_session_id', $activeSessionId)
                ->where('enrollments.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active')
                ->where('fee_records.period', $currentMonth)
                ->where('fee_records.status', 'paid');

            if ($shiftType !== 'both') {
                $paidQuery->where('enrollments.shift_type', $shiftType);
            }

            $paidCount = $paidQuery->count();
        }

        $usersCount = 0;
        if ($activeSessionId) {
            $usersCount = User::where(function($query) use ($activeSessionId, $shiftType) {
                $query->where('role', 'admin')
                    ->orWhere(function($q) use ($activeSessionId, $shiftType) {
                        $q->where('role', '!=', 'admin')
                          ->whereHas('academicSessions', function($sq) use ($activeSessionId, $shiftType) {
                              $sq->where('session_user.academic_session_id', $activeSessionId)
                                 ->where('session_user.is_active', true);
                              if ($shiftType !== 'both') {
                                  $sq->where(function($ssq) use ($shiftType) {
                                      $ssq->where('session_user.allowed_shifts', 'both')
                                          ->orWhere('session_user.allowed_shifts', $shiftType);
                                  });
                              }
                          });
                    });
            })->count();
        } else {
            $usersCount = User::count();
        }

        $stats = [
            'users'           => $usersCount,
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

        if ($activeSessionId && $activeSession) {
            $trendQuery = DB::table('attendances')
                ->join('enrollments', 'attendances.student_id', '=', 'enrollments.student_id')
                ->where('attendances.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active')
                ->whereBetween('attendances.date', [$activeSession->start_date, $activeSession->end_date]);

            if ($shiftType !== 'both') {
                $trendQuery->where('enrollments.shift_type', $shiftType);
            }

            $rows = $trendQuery->groupBy('attendances.date')
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
            $paymentsQuery = FeePayment::with(['student', 'record.class'])
                ->join('enrollments', 'fee_payments.student_id', '=', 'enrollments.student_id')
                ->where('enrollments.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active')
                ->select('fee_payments.*');

            if ($shiftType !== 'both') {
                $paymentsQuery->where('enrollments.shift_type', $shiftType);
            }

            $payments = $paymentsQuery->latest('fee_payments.created_at')->limit(5)->get();

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

            $admissionsQuery = Student::whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
                $q->where('academic_session_id', $activeSessionId)->active();
                if ($shiftType !== 'both') {
                    $q->where('shift_type', $shiftType);
                }
            });

            $admissions = $admissionsQuery->latest()->limit(5)->get();

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
            $classDistributionQuery = Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $activeSessionId);

            if ($shiftType !== 'both') {
                $classDistributionQuery->where('shift_type', $shiftType);
            }

            $classDistribution = $classDistributionQuery
                ->withCount(['students' => function($q) use ($activeSessionId, $shiftType) {
                    $q->whereHas('enrollments', function($eq) use ($activeSessionId, $shiftType) {
                        $eq->where('academic_session_id', $activeSessionId)->active();
                        if ($shiftType !== 'both') {
                            $eq->where('shift_type', $shiftType);
                        }
                    });
                }])
                ->orderByDesc('students_count')
                ->limit(6)
                ->get();
        }

        // ─── Unpaid Students List (accessibility modal) ────────────
        $unpaidStudents = collect();
        if ($activeSessionId) {
            $currentMonth = date('Y-m');

            $paidStudentIdsQuery = DB::table('fee_records')
                ->join('enrollments', 'fee_records.student_id', '=', 'enrollments.student_id')
                ->where('fee_records.academic_session_id', $activeSessionId)
                ->where('enrollments.academic_session_id', $activeSessionId)
                ->where('enrollments.status', 'active')
                ->where('fee_records.period', $currentMonth)
                ->where('fee_records.status', 'paid');

            if ($shiftType !== 'both') {
                $paidStudentIdsQuery->where('enrollments.shift_type', $shiftType);
            }

            $paidStudentIds = $paidStudentIdsQuery->pluck('fee_records.student_id');

            $unpaidStudents = Student::with('class')
                ->whereHas('enrollments', function($q) use ($activeSessionId, $shiftType) {
                    $q->where('academic_session_id', $activeSessionId)->active();
                    if ($shiftType !== 'both') {
                        $q->where('shift_type', $shiftType);
                    }
                })
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
