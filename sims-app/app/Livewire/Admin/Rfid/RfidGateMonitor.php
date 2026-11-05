<?php

namespace App\Livewire\Admin\Rfid;

use Livewire\Component;
use App\Models\Attendance;
use App\Models\AcademicSession;
use App\Models\Enrollment;
use App\Models\Classes;
use Carbon\Carbon;

class RfidGateMonitor extends Component
{
    public $todayDate;
    public $selectedClassId = '';
    public $audioChime = false;

    public function mount()
    {
        $this->authorize('attendance.manage');
        $this->todayDate = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $activeSessionId = AcademicSession::getActiveSessionId();
        $today = $this->todayDate;

        // Recent Scans (last 20 scans today)
        $recentScans = Attendance::with(['student.class', 'enrollment.class'])
            ->where('academic_session_id', $activeSessionId)
            ->where('date', $today)
            ->where('source', 'rfid')
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get();

        // Overall Today Stats
        $totalActiveStudents = Enrollment::where('academic_session_id', $activeSessionId)
            ->where('status', 'active')
            ->count();

        $totalPresentToday = Attendance::where('academic_session_id', $activeSessionId)
            ->where('date', $today)
            ->where('status', 'P')
            ->count();

        $rfidScannedToday = Attendance::where('academic_session_id', $activeSessionId)
            ->where('date', $today)
            ->where('source', 'rfid')
            ->count();

        // Class Progress Breakdown
        $classes = Classes::where('academic_session_id', $activeSessionId)
            ->orderBy('numeric_value')
            ->get()
            ->map(function ($cls) use ($activeSessionId, $today) {
                $total = Enrollment::where('class_id', $cls->id)
                    ->where('academic_session_id', $activeSessionId)
                    ->where('status', 'active')
                    ->count();

                $present = Attendance::where('academic_session_id', $activeSessionId)
                    ->where('date', $today)
                    ->where('status', 'P')
                    ->whereIn('enrollment_id', function ($q) use ($cls, $activeSessionId) {
                        $q->select('id')->from('enrollments')
                            ->where('class_id', $cls->id)
                            ->where('academic_session_id', $activeSessionId)
                            ->where('status', 'active');
                    })
                    ->count();

                return (object) [
                    'id' => $cls->id,
                    'name' => $cls->name,
                    'total' => $total,
                    'present' => $present,
                    'percentage' => $total > 0 ? round(($present / $total) * 100) : 0,
                ];
            });

        return view('livewire.admin.rfid.rfid-gate-monitor', [
            'recentScans' => $recentScans,
            'totalActiveStudents' => $totalActiveStudents,
            'totalPresentToday' => $totalPresentToday,
            'rfidScannedToday' => $rfidScannedToday,
            'classes' => $classes,
        ])->layout('components.layouts.admin', ['title' => 'RFID Smart Gate Monitor']);
    }
}
