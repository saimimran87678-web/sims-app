<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Holiday;

class AttendanceManager extends Component
{
    public $date;
    public $students = [];
    public $classes = [];
    public $selectedClassId = '';
    
    // Smart Input State
    public $absent_rolls = '';
    public $leave_rolls = '';
    
    // UI State
    public $attendance_status = 'not_submitted'; // 'submitted' | 'not_submitted'
    public $is_weekend = false;
    public $is_holiday = false;
    public $holiday_reason = '';
    public $summary = ['present' => 0, 'absent' => 0, 'leave' => 0, 'total' => 0];
    
    // Calendar view properties
    public $missedDates = [];
    public $submittedDates = [];
    public $calendarDays = [];
    public $currentMonthYearLabel = '';

    // Holiday Modal State
    public $showHolidayModal = false;
    public $holidaysList = [];
    public $holidayId = null;
    public $isMultiDay = false;
    public $holidayStart = '';
    public $holidayEnd = '';
    public $holidayReason = '';

    public function mount()
    {
        $this->authorize('students.manage');
        $this->date = Carbon::now()->format('Y-m-d');
        $this->loadClasses();
        $this->checkDateStatus();
    }
    
    public function loadClasses()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $activeSession = $activeSessionId ? \App\Models\AcademicSession::find($activeSessionId) : null;
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        $query = \App\Models\Classes::where('academic_session_id', $activeSessionId);
        if ($shiftType !== 'both') {
            $query->where('shift_type', $shiftType);
        }

        $this->classes = $query->orderBy('numeric_value')->get();
        if ($this->classes->isNotEmpty()) {
            $this->selectedClassId = $this->classes->first()->id;
            $this->loadStudentsAndAttendance();
        }
    }

    public function updatedSelectedClassId()
    {
        $this->reset(['absent_rolls', 'leave_rolls']);
        $this->loadStudentsAndAttendance();
    }

    public function updatedDate()
    {
        $this->checkDateStatus();
        // Prevent future dates (optional strict check)
        if (Carbon::parse($this->date)->isFuture()) {
            // allowing today, but not tomorrow
        }
        $this->loadStudentsAndAttendance();
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

        $holiday = Holiday::where('academic_session_id', $activeSessionId)
            ->where('shift_type', $shiftType)
            ->whereDate('start_date', '<=', $this->date)
            ->whereDate('end_date', '>=', $this->date)
            ->first();

        if ($holiday) {
            $this->is_holiday = true;
            $this->holiday_reason = $holiday->reason;
        } else {
            $this->is_holiday = false;
            $this->holiday_reason = '';
        }
    }

    public function openHolidayModal()
    {
        $this->resetHolidayForm();
        $this->loadHolidays();
        $this->showHolidayModal = true;
    }

    public function closeHolidayModal()
    {
        $this->showHolidayModal = false;
        $this->resetHolidayForm();
    }

    public function resetHolidayForm()
    {
        $this->holidayId = null;
        $this->isMultiDay = false;
        $this->holidayStart = '';
        $this->holidayEnd = '';
        $this->holidayReason = '';
    }

    public function loadHolidays()
    {
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $activeSession = $activeSessionId ? \App\Models\AcademicSession::find($activeSessionId) : null;
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        $this->holidaysList = Holiday::where('academic_session_id', $activeSessionId)
            ->where('shift_type', $shiftType)
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function saveHoliday()
    {
        if (!$this->isMultiDay) {
            $this->holidayEnd = $this->holidayStart;
        }

        $this->validate([
            'holidayStart' => 'required|date',
            'holidayEnd' => 'required|date|after_or_equal:holidayStart',
            'holidayReason' => 'nullable|string|max:255',
        ]);

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $activeSession = $activeSessionId ? \App\Models\AcademicSession::find($activeSessionId) : null;
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');

        Holiday::updateOrCreate(
            ['id' => $this->holidayId],
            [
                'academic_session_id' => $activeSessionId,
                'shift_type' => $shiftType,
                'start_date' => $this->holidayStart,
                'end_date' => $this->holidayEnd,
                'reason' => $this->holidayReason,
            ]
        );

        $this->resetHolidayForm();
        $this->loadHolidays();
        $this->checkDateStatus(); // Update current view if it affects the selected date
        session()->flash('holiday_message', 'Holiday saved successfully!');
    }

    public function editHoliday($id)
    {
        $holiday = Holiday::find($id);
        if ($holiday) {
            $this->holidayId = $holiday->id;
            $this->holidayStart = $holiday->start_date->format('Y-m-d');
            $this->holidayEnd = $holiday->end_date->format('Y-m-d');
            $this->isMultiDay = $this->holidayStart !== $this->holidayEnd;
            $this->holidayReason = $holiday->reason;
        }
    }

    public function deleteHoliday($id)
    {
        Holiday::destroy($id);
        $this->loadHolidays();
        $this->checkDateStatus();
        session()->flash('holiday_message', 'Holiday revoked successfully!');
    }

    public function loadStudentsAndAttendance()
    {
        if (!$this->selectedClassId) {
            $this->students = collect([]);
            return;
        }

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $selectedClass = \App\Models\Classes::withoutGlobalScope('active_session')->find($this->selectedClassId);
        $classShift = $selectedClass ? $selectedClass->shift_type : 'morning';

        $this->students = DB::table('students')
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.class_id', $this->selectedClassId)
            ->where('enrollments.academic_session_id', $activeSessionId)
            ->where('enrollments.shift_type', $classShift)
            ->where('enrollments.status', 'active')
            ->select('students.id', 'students.name', 'students.phone', 'students.gender', 'enrollments.roll_number as roll_no')
            ->orderByRaw('CAST(enrollments.roll_number AS INTEGER) ASC')
            ->get();
            
        $this->summary['total'] = collect($this->students)->count();
        
        $this->loadAttendance();
        $this->calculateMonthAttendanceGrid();
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
        
        // Check for invalid rolls to warn user (optional, but good for UX)
        $invalidAbsent = array_diff($absentList, $validRolls);
        $invalidLeave = array_diff($leaveList, $validRolls);
        
        if (!empty($invalidAbsent) || !empty($invalidLeave)) {
             $invalid = array_merge($invalidAbsent, $invalidLeave);
             // We could flash a message or set a property, but for now let's just ensure counts are correct 
             // and maybe prevent saving or show a generic error?
             // Since the user just said "it marks", fixing the counts is the primary fix.
             // We will rely on correct counts.
        }

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
            
            // Send WhatsApp notifications (async, don't block UI)
            $this->sendWhatsAppNotifications($absentStudents, $leaveStudents);
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error saving attendance: ' . $e->getMessage());
        }
    }

    protected function sendWhatsAppNotifications(array $absentStudents, array $leaveStudents): void
    {
        try {
            $whatsapp = app(\App\Services\WhatsAppService::class);
            $isConnected = $whatsapp->isConnected();

            $totalSent = 0;
            $formattedDate = \Carbon\Carbon::parse($this->date)->format('d M Y');

            // Send to absent students
            if (!empty($absentStudents)) {
                $result = $whatsapp->sendAttendanceNotifications($absentStudents, 'A', $formattedDate);
                $totalSent += $result['sent'];
            }

            // Send to leave students
            if (!empty($leaveStudents)) {
                $result = $whatsapp->sendAttendanceNotifications($leaveStudents, 'L', $formattedDate);
                $totalSent += $result['sent'];
            }

            if ($totalSent > 0) {
                if ($isConnected) {
                    session()->flash('message', "Attendance saved! $totalSent parent notification(s) queued for WhatsApp delivery.");
                } else {
                    session()->flash('message', "Attendance saved! WhatsApp service is offline, but $totalSent notification(s) have been queued and will send once the service is connected.");
                }
            } else {
                session()->flash('message', 'Attendance saved! No notifications sent (no phone numbers available).');
            }

        } catch (\Exception $e) {
            // Don't fail the attendance save, just log the error
            \Illuminate\Support\Facades\Log::error('WhatsApp notification error: ' . $e->getMessage());
            session()->flash('message', 'Attendance saved! (WhatsApp notifications failed)');
        }
    }

    public function selectDate($date)
    {
        $this->date = $date;
        $this->checkDateStatus();
        $this->loadStudentsAndAttendance();
    }

    public function calculateMonthAttendanceGrid()
    {
        if (!$this->selectedClassId || empty($this->students)) {
            $this->missedDates = [];
            $this->submittedDates = [];
            $this->calendarDays = [];
            return;
        }

        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        $sessionObj = \App\Models\AcademicSession::find($activeSessionId);
        if (!$sessionObj) {
            $this->missedDates = [];
            $this->submittedDates = [];
            $this->calendarDays = [];
            return;
        }

        $sessionStart = Carbon::parse($sessionObj->start_date);
        $sessionEnd = Carbon::parse($sessionObj->end_date);
        
        $activeDate = Carbon::parse($this->date);
        $startOfMonth = $activeDate->copy()->startOfMonth();
        $endOfMonth = $activeDate->copy()->endOfMonth();

        $this->currentMonthYearLabel = $activeDate->format('F Y');

        $startDate = $startOfMonth->copy();
        if ($startDate->lt($sessionStart)) {
            $startDate = $sessionStart->copy();
        }
        
        $today = Carbon::today();
        $endDate = $endOfMonth->copy();
        if ($endDate->gt($sessionEnd)) {
            $endDate = $sessionEnd->copy();
        }

        // Fetch holidays
        $activeSession = \App\Models\AcademicSession::find($activeSessionId);
        $isRegular = ($activeSession && $activeSession->shift_type === 'Regular');
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
        $submitted = [];

        $current = $startOfMonth->copy();
        $this->calendarDays = [];

        // Pad start
        $dayOfWeek = $startOfMonth->dayOfWeek;
        for ($i = 0; $i < $dayOfWeek; $i++) {
            $this->calendarDays[] = [
                'date' => null,
                'day' => '',
                'status' => 'empty',
            ];
        }

        while ($current->lte($endOfMonth)) {
            $dateStr = $current->format('Y-m-d');
            $isWeekend = $weekendMode === 'sun_only' ? $current->isSunday() : $current->isWeekend();

            if ($current->gt($today)) {
                $status = 'future';
            } elseif ($current->lt($sessionStart) || $current->gt($sessionEnd)) {
                $status = 'outside_session';
            } elseif ($isWeekend) {
                $status = 'weekend';
            } else {
                $isHoliday = $holidays->contains(function ($h) use ($dateStr) {
                    $start = substr($h->start_date, 0, 10);
                    $end = substr($h->end_date, 0, 10);
                    return $dateStr >= $start && $dateStr <= $end;
                });

                if ($isHoliday) {
                    $status = 'holiday';
                } else {
                    if (in_array($dateStr, $recordedDates)) {
                        $status = 'submitted';
                        $submitted[] = $dateStr;
                    } else {
                        $status = 'missed';
                        $missed[] = $dateStr;
                    }
                }
            }

            $this->calendarDays[] = [
                'date' => $dateStr,
                'day' => $current->day,
                'status' => $status,
                'is_active' => $dateStr === $this->date,
            ];

            $current->addDay();
        }

        $this->missedDates = $missed;
        $this->submittedDates = $submitted;
    }

    public function render()
    {
        return view('livewire.admin.attendance-manager')->layout('components.layouts.admin', ['title' => 'Attendance']);
    }
}
