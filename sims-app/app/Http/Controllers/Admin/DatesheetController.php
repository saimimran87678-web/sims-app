<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Exam;
use App\Models\ExamSchedule;

class DatesheetController extends Controller
{
    public function show($examId)
    {
        $exam = Exam::with('academicSession')->findOrFail($examId);
        
        // Fetch all schedules from the new table
        $schedules = \App\Models\DatesheetSchedule::with(['class'])
            ->where('exam_id', $examId)
            ->whereNotNull('exam_date')
            ->orderBy('exam_date')
            ->get();

        // 1. Get Unique Dates
        $dates = $schedules->pluck('exam_date')
            ->unique()
            ->sort()
            ->values();

        // 2. Get Unique Classes (ordered by numeric value)
        $classes = $schedules->pluck('class')
            ->unique('id')
            ->sortBy(function($class) {
                 return $class->numeric_value ?? 999;
            })
            ->values();

        // 3. Build Matrix: [date][class_id] => Subject Name
        $matrix = [];
        foreach ($schedules as $sched) {
            // New model has 'subject' as string
            $matrix[$sched->exam_date][$sched->class_id] = $sched->subject;
        }

        return view('admin.exams.datesheet', compact('exam', 'dates', 'classes', 'matrix'));
    }
}
