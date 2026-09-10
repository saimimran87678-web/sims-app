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
        ->with('teacher')
        ->get()
        ->groupBy('period_no');

    foreach ($entries as $period => $records) {
        $teachers = $records->pluck('teacher_id')->unique();
        
        // Case 1: Multiple teachers for the same slot across the week (The "Substitue" issue)
        if ($teachers->count() > 1) {
            $details = [];
            foreach ($records as $r) {
                $tName = $r->teacher ? $r->teacher->name : 'No Teacher';
                $tId = $r->teacher_id;
                // Append instead of overwrite to see duplicates
                if (isset($details[$r->day])) {
                    $details[$r->day] .= " / $tName ($tId)";
                } else {
                    $details[$r->day] = "$tName ($tId)";
                }
            }
            // Fill missing days
            foreach ($weekDays as $day) {
                if (!isset($details[$day])) $details[$day] = 'Unassigned';
            }
            
            // Sort by day order
            $sortedDetails = [];
            foreach ($weekDays as $day) $sortedDetails[$day] = $details[$day];

            $inconsistencies[] = [
                'type' => 'Split/Duplicate Teacher',
                'class' => $class->name,
                'period' => $period,
                'teachers_count' => $teachers->count(),
                'details' => $sortedDetails
            ];
        }
    }
}

file_put_contents('audit_result.json', json_encode($inconsistencies, JSON_PRETTY_PRINT));
echo "Audit complete. Saved to audit_result.json";
