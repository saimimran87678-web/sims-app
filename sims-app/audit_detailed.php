<?php

use App\Models\Classes;
use App\Models\Timetable;

$inconsistencies = [];
$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

$classes = Classes::all();

foreach ($classes as $class) {
    // Get all timetable entries for this class, strictly for weekdays
    $entries = Timetable::where('class_id', $class->id)
        ->whereIn('day', $weekDays)
        ->with(['teacher', 'subject'])
        ->get()
        ->groupBy('period_no');

    foreach ($entries as $period => $records) {
        // Group by Day to check for same-day splits
        $byDay = $records->groupBy('day');
        $isValidSplit = false;
        
        foreach ($byDay as $day => $dayRecords) {
            if ($dayRecords->count() > 1) {
                $subjects = $dayRecords->pluck('subject.name')->unique();
                if ($subjects->count() > 1) {
                    $isValidSplit = true; // Different subjects on the same day = Valid Split (Comp/Bio)
                }
            }
        }

        if ($isValidSplit) {
            continue; // Skip valid splits as requested
        }

        // Now check for Inconsistent Days (Teacher changing across the week)
        $teachers = $records->pluck('teacher_id')->unique();
        
        if ($teachers->count() > 1) {
            // Check if it's just a "Subject Split" that happens to be formatted weirdly?
            // If the subjects are consistently different for different teachers, maybe it's a valid "Alternating Subject" schedule?
            // But User said "Permanent timetable for all days", implying same teacher/subject daily.
            
            // Logic: If Teacher A is Mon/Wed/Thu and Teacher B is Tue/Fri, this is the error to fix.
            
            $details = [];
            foreach ($records as $r) {
                $tName = $r->teacher ? $r->teacher->name : 'No Teacher';
                $sName = $r->subject ? $r->subject->name : 'No Subject';
                $details[$r->day] = "$tName ($sName)";
            }
            
             // Sort by day order
            $sortedDetails = [];
            foreach ($weekDays as $day) $sortedDetails[$day] = $details[$day] ?? 'Unassigned';

            // Identify Majority Teacher
            $teacherCounts = $records->groupBy('teacher_id')->map->count()->sortDesc();
            $majorityTeacherId = $teacherCounts->keys()->first();
            $majorityTeacher = $records->where('teacher_id', $majorityTeacherId)->first()->teacher;
            
            $inconsistencies[] = [
                'type' => 'Inconsistent Week',
                'class' => $class->name,
                'class_id' => $class->id,
                'period' => $period,
                'details' => $sortedDetails,
                'majority_teacher_id' => $majorityTeacherId,
                'majority_teacher_name' => $majorityTeacher->name ?? 'Unknown',
                'majority_days' => $teacherCounts->first()
            ];
        }
    }
}

file_put_contents('audit_detailed_result.json', json_encode($inconsistencies, JSON_PRETTY_PRINT));
echo "Detailed Audit complete.";
