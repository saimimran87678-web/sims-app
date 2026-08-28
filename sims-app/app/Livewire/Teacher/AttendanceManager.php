<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Holiday;

class AttendanceManager extends Component
{
    public $date;
    public $students = [];
    public $classId;
    public $className = '';
    
    // Smart Input State
    public $absent_rolls = '';
    public $leave_rolls = '';
    
    // UI State
    public $attendance_status = 'not_submitted'; // 'submitted' | 'not_submitted'
    public $is_weekend = false;
    public $is_holiday = false;
    public $holiday_reason = '';
    public $summary = ['present' => 0, 'absent' => 0, 'leave' => 0, 'total' => 0];
    public $missedDates = [];

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $classId = Auth::user()->getSessionClassId($activeSessionId);

        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        if ($classId) {
            $class = \App\Models\Classes::withoutGlobalScope('active_session')->find($classId);
            if ($class && ($shiftType === 'both' || $class->shift_type === $shiftType)) {
                $this->classId = $classId;
                $this->className = $class->name;
            }
        }

        if (!$this->classId) {
            session()->flash('error', 'You are not assigned to any class in this shift.');
            return;
        }

        $this->fetchStudents();
        $this->loadAttendance();
        $this->checkDateStatus();
        $this->calculateMissedDates();
    }

    public function updatedDate()
    {
        $this->checkDateStatus();
        // Prevent future dates (optional strict check)
        if (Carbon::parse($this->date)->isFuture()) {
            // allowing today, but not tomorrow
        }
        $this->loadAttendance();
    }

    public function checkDateStatus()
    {
        $d = Carbon::parse($this->date);
        $weekendMode = \App\Models\Setting::get('weekend_mode', 'sat_sun');

        $this->is_weekend = $weekendMode === 'sun_only'
            ? $d->isSunday()           // Only Sunday is a weekend
            : $d->isWeekend();         // Saturday + Sunday are weekends

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $activeSession = $activeSessionId ? \App\Models\AcademicSession::find($activeSessionId) : null;
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        $holiday = Holiday::whereDate('start_date', '<=', $this->date)
            ->whereDate('end_date', '>=', $this->date)
            ->where('academic_session_id', $activeSessionId)
            ->where('shift_type', $shiftType)
            ->first();

        if ($holiday) {
            $this->is_holiday = true;
            $this->holiday_reason = $holiday->reason;
        } else {
            $this->is_holiday = false;
            $this->holiday_reason = '';
        }
    }

    public function fetchStudents()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $selectedClass = \App\Models\Classes::withoutGlobalScope('active_session')->find($this->classId);
        $classShift = $selectedClass ? $selectedClass->shift_type : 'morning';

        $this->students = DB::table('students')
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.class_id', $this->classId)
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.shift_type', $classShift)
            ->where('enrollments.status', 'active')
            ->select('students.*', 'enrollments.roll_number as roll_no', 'enrollments.status')
            ->get();
            
        $this->summary['total'] = collect($this->students)->count();
    }

    public function loadAttendance()
    {
        // Fetch existing records for this class and date
        $records = DB::table('attendances')
            ->whereIn('student_id', collect($this->students)->pluck('id'))
            ->where('date', $this->date)
            ->get();

        if ($records->isEmpty()) {
            $this->attendance_status = 'not_submitted';
            $this->absent_rolls = '';
            $this->leave_rolls = '';
            $this->calculateSummary();
            return;
        }

        $this->attendance_status = 'submitted';
        
        $absent = [];
        $leave = [];

        foreach ($records as $record) {
            $student = collect($this->students)->firstWhere('id', $record->student_id);
            if (!$student) continue;

            if ($record->status === 'A') {
                $absent[] = $student->roll_no;
            } elseif ($record->status === 'L') {
                $leave[] = $student->roll_no;
            }
        }

        $this->absent_rolls = implode(', ', $absent);
        $this->leave_rolls = implode(', ', $leave);
        
        $this->calculateSummary();
    }

    public function calculateSummary()
    {
        $absentList = $this->parseRolls($this->absent_rolls);
        $leaveList = $this->parseRolls($this->leave_rolls);
        
        // Get valid class rolls
        $validRolls = collect($this->students)->pluck('roll_no')->map(fn($r) => (string)$r)->toArray();

        // Filter out invalid rolls
        $validAbsent = array_intersect($absentList, $validRolls);
        $validLeave = array_intersect($leaveList, $validRolls);

        $this->summary['absent'] = count($validAbsent);
        $this->summary['leave'] = count($validLeave);
        $this->summary['present'] = $this->summary['total'] - $this->summary['absent'] - $this->summary['leave'];
    }

    // Update summary when user types
    public function updatedAbsentRolls() { $this->calculateSummary(); }
    public function updatedLeaveRolls() { $this->calculateSummary(); }

    private function parseRolls($string)
    {
        if (empty(trim($string))) return [];
        
        return collect(preg_split('/[\s,]+/', $string))
            ->map(fn($s) => trim($s))
            ->filter(fn($s) => $s !== '')
            ->all();
    }

    public function save()
    {
        if ($this->is_weekend) {
            session()->flash('error', 'Cannot mark attendance on weekends.');
            return;
        }

        if ($this->is_holiday) {
            session()->flash('error', 'Cannot mark attendance on holidays.');
            return;
        }

        $absentRolls = $this->parseRolls($this->absent_rolls);
        $leaveRolls = $this->parseRolls($this->leave_rolls);

        // Validation: Check for invalid roll numbers
        $validRolls = collect($this->students)->pluck('roll_no')->map(fn($r) => (string)$r)->toArray();
        
        $invalidAbsent = array_diff($absentRolls, $validRolls);
        $invalidLeave = array_diff($leaveRolls, $validRolls);
        
        if (!empty($invalidAbsent) || !empty($invalidLeave)) {
            $invalid = array_merge($invalidAbsent, $invalidLeave);
            $invalidList = implode(', ', $invalid);
            session()->flash('error', "Cannot save: The following roll numbers do not exist in this class: $invalidList");
            return;
        }

        DB::beginTransaction();
        try {
            $absentStudents = [];
            $leaveStudents = [];

            foreach ($this->students as $student) {
                $roll = (string) $student->roll_no;
                $status = 'P';

                if (in_array($roll, $absentRolls)) {
                    $status = 'A';
                    $absentStudents[] = [
                        'id' => $student->id,
                        'name' => $student->name,
                        'roll_no' => $student->roll_no,
                        'phone' => $student->phone ?? null,
                        'gender' => $student->gender ?? null
                    ];
                } elseif (in_array($roll, $leaveRolls)) {
                    $status = 'L';
                    $leaveStudents[] = [
                        'id' => $student->id,
                        'name' => $student->name,
                        'roll_no' => $student->roll_no,
                        'phone' => $student->phone ?? null,
                        'gender' => $student->gender ?? null
                    ];
                }

                $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
                DB::table('attendances')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'date' => $this->date,
                    ],
                    [
                        'academic_session_id' => $activeSessionId,
                        'status' => $status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
            DB::commit();
            
            $this->attendance_status = 'submitted';
            $this->calculateMissedDates();
            
            // Send WhatsApp notifications
            $this->sendWhatsAppNotifications($absentStudents, $leaveStudents);
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error saving attendance: ' . $e->getMessage());
        }
    }

    protected function sendWhatsAppNotifications(array $absentStudents, array $leaveStudents): void
    {
        // DEBUG: Log what we're receiving
        \Illuminate\Support\Facades\Log::info('WhatsApp Notify Debug', [
            'absentStudents' => $absentStudents,
            'leaveStudents' => $leaveStudents,
        ]);

        // Check if there's anything to notify
        $studentsToNotify = array_merge($absentStudents, $leaveStudents);
        if (empty($studentsToNotify)) {
            session()->flash('message', 'Attendance saved! No absent/leave students to notify.');
            return;
        }

        // Check how many have phone numbers
        $withPhone = array_filter($studentsToNotify, fn($s) => !empty($s['phone']));
        if (empty($withPhone)) {
            session()->flash('message', 'Attendance saved! No notifications sent (no phone numbers available).');
            return;
        }

        try {
            $whatsapp = app(\App\Services\WhatsAppService::class);
            $isConnected = $whatsapp->isConnected();

            $totalSent = 0;
            $totalFailed = 0;
            $formattedDate = \Carbon\Carbon::parse($this->date)->format('d M Y');

            if (!empty($absentStudents)) {
                $result = $whatsapp->sendAttendanceNotifications($absentStudents, 'A', $formattedDate);
                $totalSent += $result['sent'];
                $totalFailed += $result['failed'];
            }

            if (!empty($leaveStudents)) {
                $result = $whatsapp->sendAttendanceNotifications($leaveStudents, 'L', $formattedDate);
                $totalSent += $result['sent'];
                $totalFailed += $result['failed'];
            }

            if ($totalSent > 0) {
                if ($isConnected) {
                    session()->flash('message', "Attendance saved! $totalSent parent notification(s) queued for WhatsApp delivery.");
                } else {
                    session()->flash('message', "Attendance saved! WhatsApp service is offline, but $totalSent notification(s) have been queued and will send once the service is connected.");
                }
            } elseif ($totalFailed > 0) {
                session()->flash('warning', "Attendance saved! Notifications failed ($totalFailed). Check WhatsApp connection.");
            } else {
                session()->flash('message', 'Attendance saved! Notifications already sent earlier or skipped.');
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsApp notification error: ' . $e->getMessage());
            session()->flash('warning', 'Attendance saved! (WhatsApp error: ' . substr($e->getMessage(), 0, 50) . ')');
        }
    }

    public function getAbsentOrLeaveStudentsProperty()
    {
        if ($this->attendance_status !== 'submitted') {
            return collect();
        }

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $shiftType = session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        return DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.class_id', $this->classId)
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.shift_type', $shiftType)
            ->where('attendances.date', $this->date)
            ->whereIn('attendances.status', ['A', 'L'])
            ->select('students.id', 'students.name', 'enrollments.roll_number as roll_no', 'students.phone', 'attendances.status')
            ->orderByRaw('CAST(enrollments.roll_number AS INTEGER) ASC')
            ->get();
    }

    public function markAsLate($studentId)
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $shiftType = session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $attendance = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.class_id', $this->classId)
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.shift_type', $shiftType)
            ->where('attendances.student_id', $studentId)
            ->where('attendances.date', $this->date)
            ->whereIn('attendances.status', ['A', 'L'])
            ->select('attendances.*', 'students.name', 'enrollments.roll_number as roll_no', 'students.phone')
            ->first();

        if (!$attendance) {
            session()->flash('error', 'Student not found in absent/leave list.');
            return;
        }

        DB::beginTransaction();
        try {
            DB::table('attendances')
                ->where('id', $attendance->id)
                ->update([
                    'status' => 'P',
                    'updated_at' => now(),
                ]);

            if (!empty($attendance->phone)) {
                $now = now();
                $formattedTime = $now->format('h:i A');
                
                $messageText = \App\Helpers\PhoneHelper::getLateMessage($attendance->name, $attendance->roll_no, $formattedTime, null, null, $studentId);

                DB::table('whatsapp_queue')->insert([
                    'phone' => $attendance->phone,
                    'message' => $messageText,
                    'status' => 'pending',
                    'priority' => 1, // High priority
                    'student_id' => $studentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::commit();

            $this->loadAttendance();
            session()->flash('message', "Student {$attendance->name} marked as Late. Present status saved and parent notification queued.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error marking student as late: ' . $e->getMessage());
        }
    }

    public function selectDate($date)
    {
        $this->date = $date;
        $this->checkDateStatus();
        $this->loadAttendance();
    }

    public function calculateMissedDates()
    {
        if (!$this->classId || empty($this->students)) {
            $this->missedDates = [];
            return;
        }

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        if (!$sessionObj) {
            $this->missedDates = [];
            return;
        }

        $sessionStart = Carbon::parse($sessionObj->start_date);
        $today = Carbon::today();

        if ($sessionStart->isFuture()) {
            $this->missedDates = [];
            return;
        }

        // Scan past 30 days
        $startDate = Carbon::today()->subDays(30);
        if ($startDate->lt($sessionStart)) {
            $startDate = $sessionStart;
        }
        $endDate = Carbon::yesterday();

        if ($startDate->gt($endDate)) {
            $this->missedDates = [];
            return;
        }

        // Fetch holidays
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        $holidays = DB::table('holidays')
            ->where('academic_session_id', $activeSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('start_date', '<=', $endDate->format('Y-m-d'))
            ->whereDate('end_date', '>=', $startDate->format('Y-m-d'))
            ->get();

        // Fetch recorded attendance dates
        $studentIds = collect($this->students)->pluck('id')->toArray();
        $recordedDates = DB::table('attendances')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct()
            ->pluck('date')
            ->toArray();

        $weekendMode = \App\Models\Setting::get('weekend_mode', 'sat_sun');

        $missed = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->format('Y-m-d');

            // Check weekend
            $isWeekend = $weekendMode === 'sun_only'
                ? $current->isSunday()
                : $current->isWeekend();

            if ($isWeekend) {
                $current->addDay();
                continue;
            }

            // Check holiday
            $isHoliday = $holidays->contains(function ($h) use ($dateStr) {
                $start = substr($h->start_date, 0, 10);
                $end = substr($h->end_date, 0, 10);
                return $dateStr >= $start && $dateStr <= $end;
            });

            if ($isHoliday) {
                $current->addDay();
                continue;
            }

            // Check if recorded
            if (!in_array($dateStr, $recordedDates)) {
                $missed[] = $dateStr;
            }

            $current->addDay();
        }

        $this->missedDates = $missed;
    }

    public function render()
    {
        return view('livewire.teacher.attendance-manager')->layout('components.layouts.teacher', ['title' => 'Attendance']);
    }
}
