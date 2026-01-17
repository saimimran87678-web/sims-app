<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\DatesheetSchedule;
use App\Models\Classes;
use App\Models\MarksConfig;
use Illuminate\Http\Request;

class DatesheetController extends Controller
{
    public function print($examId)
    {
        $exam = Exam::findOrFail($examId);
        
        // 1. Fetch Classes Grouped
        $allClasses = Classes::orderBy('numeric_value', 'desc')->get();
        // Determine "Grades"
        $grades = $allClasses->groupBy(function ($class) {
             return $class->numeric_value ?? intval(preg_replace('/[^0-9]+/', '', $class->name), 10);
        })->sortKeysDesc();

        // 2. Fetch Schedule Data
        $schedules = DatesheetSchedule::where('exam_id', $examId)
            ->get()
            ->groupBy('exam_date'); // Key: Date -> Value: Collection of Rows
            
        // 3. Fetch Marks Data
        $marksData = MarksConfig::where('exam_id', $examId)
            ->get()
            ->groupBy('class_id')
            ->map(function ($items) {
                return $items->pluck('total_marks', 'subject');
            });

        return view('admin.datesheet.print', compact('exam', 'grades', 'schedules', 'marksData'));
    }
}
