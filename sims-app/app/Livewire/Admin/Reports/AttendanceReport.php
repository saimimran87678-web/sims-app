<?php

namespace App\Livewire\Admin\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReport extends Component
{
    public $classes = [];
    public $selectedClassId = '';
    public $selectedMonth = ''; // YYYY-MM
    
    public $reportData = [];
    public $isLoading = false;
    public $noDataAvailable = false;

    public function mount()
    {
        $this->classes = DB::table('classes')->orderBy('numeric_value')->get();
        $this->selectedMonth = Carbon::now()->format('Y-m');
    }

    public function generate()
    {
        $this->validate([
            'selectedClassId' => 'required',
            'selectedMonth' => 'required',
        ]);

        $this->isLoading = true;
        $this->noDataAvailable = false;
        $this->reportData = [];

        try {
            // 1. Fetch Students
            $students = DB::table('students')
                ->where('class_id', $this->selectedClassId)
                ->orderBy('roll_no')
                ->get();

            if ($students->isEmpty()) {
                $this->reportData = [];
                $this->isLoading = false;
                return;
            }

            // 2. Fetch Attendance Records for the month
            $startOfMonth = Carbon::parse($this->selectedMonth)->startOfMonth()->format('Y-m-d');
            $endOfMonth = Carbon::parse($this->selectedMonth)->endOfMonth()->format('Y-m-d');

            $studentIds = $students->pluck('id')->toArray();

            $records = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get();

            // 3. Process Data
            $totalDays = Carbon::parse($this->selectedMonth)->daysInMonth; // Or count distinct dates in DB? 
            // Better: use only days where attendance was taken? React logic calculated totalDays based on existing records.
            // Let's count *teaching days* (unique dates in attendance table for this class) to be accurate.
            
            $teachingDates = $records->pluck('date')->unique();
            $totalTeachingDays = $teachingDates->count();

            // Check if any attendance data exists for this month
            if ($totalTeachingDays === 0) {
                $this->noDataAvailable = true;
                $this->isLoading = false;
                return;
            }

            $this->reportData = $students->map(function ($student) use ($records, $totalTeachingDays) {
                // Filter records for this student
                $studentRecords = $records->where('student_id', $student->id);
                
                $present = $studentRecords->where('status', 'P')->count();
                $absent = $studentRecords->where('status', 'A')->count();
                $leave = $studentRecords->where('status', 'L')->count();

                // If "Smart Attendance" marks everyone present by default, we only stored exceptions?
                // Wait, my AttendanceManager implementation does:
                // updateOrInsert status='P', 'A', or 'L'. 
                // So every student has a row for every day attendance was taken.
                // So counting rows is correct.

                $percentage = $totalTeachingDays > 0 
                    ? round((($present + $leave) / $totalTeachingDays) * 100, 1) 
                    : 0;

                return [
                    'id' => $student->id,
                    'roll_no' => $student->roll_no,
                    'name' => $student->name,
                    'total_days' => $totalTeachingDays,
                    'present' => $present,
                    'absent' => $absent,
                    'leave' => $leave,
                    'percentage' => $percentage,
                ];
            })->toArray(); // Array of arrays

        } catch (\Exception $e) {
            session()->flash('error', 'Error generating report: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function downloadCsv()
    {
        if (empty($this->reportData)) return;

        $fileName = 'Attendance_Report_' . $this->selectedClassId . '_' . $this->selectedMonth . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            
            // Header
            fputcsv($handle, ['Roll No', 'Name', 'Total Days', 'Present', 'Absent', 'Leave', 'Percentage']);

            foreach ($this->reportData as $row) {
                fputcsv($handle, [
                    $row['roll_no'],
                    $row['name'],
                    $row['total_days'],
                    $row['present'],
                    $row['absent'],
                    $row['leave'],
                    $row['percentage'] . '%'
                ]);
            }

            fclose($handle);
        }, $fileName);
    }

    public function render()
    {
        return view('livewire.admin.reports.attendance-report')->layout('components.layouts.admin', ['title' => 'Attendance Report']);
    }
}
