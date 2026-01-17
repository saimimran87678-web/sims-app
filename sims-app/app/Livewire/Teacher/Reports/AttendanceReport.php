<?php

namespace App\Livewire\Teacher\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Models\Classes;

class AttendanceReport extends Component
{
    public $classes = [];
    public $selectedClass = '';
    public $className = '';
    public $selectedMonth = ''; // YYYY-MM
    
    public $reportData = [];
    public $isLoading = false;
    public $hasError = false;
    public $errorMessage = '';
    public $noDataAvailable = false;

    public function mount()
    {
        $this->selectedMonth = Carbon::now()->format('Y-m');
        $this->loadClasses();
    }

    public function loadClasses()
    {
        $user = Auth::user();
        
        // 1. View All Classes Permission
        if ($user->can('reports.view-all-classes') || $user->can('reports.view')) {
           // Check if manual restriction exists FIRST
           $restrictedIds = DB::table('user_class_access')
                ->where('user_id', $user->id)
                ->pluck('class_id')
                ->toArray();
           
           if (!empty($restrictedIds)) {
               $this->classes = Classes::whereIn('id', $restrictedIds)->orderBy('numeric_value')->get();
           } elseif ($user->can('reports.view-all-classes')) {
               $this->classes = Classes::orderBy('numeric_value')->get();
           } else {
               // Fallback for simple 'reports.view' if no explicit allow-all (behaves like own class only unless otherwise specified)
               $this->classes = $user->class_id ? Classes::where('id', $user->class_id)->get() : collect();
           }
        } 
        // 2. Default: Own Class
        elseif ($user->class_id) {
            $this->classes = Classes::where('id', $user->class_id)->get();
        } else {
            $this->classes = collect();
        }

        if ($this->classes->isNotEmpty()) {
            // Default to own class if available in the list, otherwise first
            if ($user->class_id && $this->classes->where('id', $user->class_id)->isNotEmpty()) {
                $this->selectedClass = $user->class_id;
            } else {
                $this->selectedClass = $this->classes->first()->id;
            }
            $this->updatedSelectedClass();
        } else {
             $this->hasError = true;
             $this->errorMessage = 'You are not assigned to any class. Please contact the administrator.';
        }
    }

    public function updatedSelectedClass()
    {
        if ($this->selectedClass) {
            $this->className = Classes::find($this->selectedClass)->name ?? '';
            $this->reportData = [];
            $this->noDataAvailable = false;
        }
    }

    public function generate()
    {
        if ($this->hasError || !$this->selectedClass) return;

        $this->validate([
            'selectedMonth' => 'required',
            'selectedClass' => 'required'
        ]);

        $this->isLoading = true;
        $this->reportData = [];
        $this->noDataAvailable = false;

        try {
            // 1. Fetch Students
            $students = DB::table('students')
                ->where('class_id', $this->selectedClass)
                ->orderBy('roll_no')
                ->get();

            if ($students->isEmpty()) {
                $this->isLoading = false;
                $this->noDataAvailable = true; // Treating empty class as no data
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
            // Count unique dates where attendance was recorded for ANY student in this class
            $teachingDates = $records->pluck('date')->unique();
            $totalTeachingDays = $teachingDates->count();

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
            })->toArray();

        } catch (\Exception $e) {
            session()->flash('error', 'Error generating report: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function downloadCsv()
    {
        if (empty($this->reportData)) return;

        $fileName = 'Attendance_Report_' . str_replace(' ', '_', $this->className) . '_' . $this->selectedMonth . '.csv';

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
        return view('livewire.teacher.reports.attendance-report')->layout('components.layouts.teacher', ['title' => 'Attendance Report']);
    }
}
