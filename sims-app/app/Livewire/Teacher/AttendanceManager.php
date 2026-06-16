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

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->classId = Auth::user()->class_id;

        if (!$this->classId) {
            session()->flash('error', 'You are not assigned to any class.');
            return;
        }

        // Fetch class name
        $this->className = DB::table('classes')->where('id', $this->classId)->value('name') ?? 'Unknown Class';

        $this->fetchStudents();
        $this->loadAttendance();
        $this->checkDateStatus();
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

        $holiday = Holiday::where('start_date', '<=', $this->date)
            ->where('end_date', '>=', $this->date)
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
        $this->students = DB::table('students')
            ->where('class_id', $this->classId)
            ->orderByRaw('CAST(roll_no AS INTEGER) ASC') // Ensure ordered by roll number for easier checking
            ->get();
            
        $this->summary['total'] = $this->students->count();
    }

    public function loadAttendance()
    {
        // Fetch existing records for this class and date
        $records = DB::table('attendances')
            ->whereIn('student_id', $this->students->pluck('id'))
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
            $student = $this->students->firstWhere('id', $record->student_id);
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
        $validRolls = $this->students->pluck('roll_no')->map(fn($r) => (string)$r)->toArray();

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
        
        return collect(preg_split('/\s+/', $string))
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
        $validRolls = $this->students->pluck('roll_no')->map(fn($r) => (string)$r)->toArray();
        
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
                        'phone' => $student->phone ?? null
                    ];
                } elseif (in_array($roll, $leaveRolls)) {
                    $status = 'L';
                    $leaveStudents[] = [
                        'id' => $student->id,
                        'name' => $student->name,
                        'roll_no' => $student->roll_no,
                        'phone' => $student->phone ?? null
                    ];
                }

                DB::table('attendances')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'date' => $this->date,
                    ],
                    [
                        'status' => $status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
            DB::commit();
            
            $this->attendance_status = 'submitted';
            
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
            
            if (!$whatsapp->isConnected()) {
                session()->flash('message', 'Attendance saved! WhatsApp notifications skipped (not connected).');
                return;
            }

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
                session()->flash('message', "Attendance saved! $totalSent parent notification(s) sent via WhatsApp.");
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

    public function render()
    {
        return view('livewire.teacher.attendance-manager')->layout('components.layouts.teacher', ['title' => 'Attendance']);
    }
}
