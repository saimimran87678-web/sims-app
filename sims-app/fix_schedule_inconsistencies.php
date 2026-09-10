<?php

use App\Models\Timetable;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;

// Define the fixes based on the "Majority Rule" (Mon/Wed/Thu overrides Tue/Fri)
// structure: ['class' => 'Name', 'period' => N, 'source_day' => 'DayName']
$fixes = [
    ['class' => 'Class 7A', 'period' => 8, 'source_day' => 'Tuesday'], // Majority was Tue-Fri (Shahjahan), Mon was outlier (Amir)
    ['class' => 'Class 7D', 'period' => 1, 'source_day' => 'Monday'],  // Majority Mon-Thu (Yosaf), Fri outlier (Abdul Ghafar)
    ['class' => 'Class 11C', 'period' => 1, 'source_day' => 'Monday'], // Mon/Wed/Thu (Fahad), Tue/Fri outlier (Owais)
    ['class' => 'Class 11C', 'period' => 7, 'source_day' => 'Monday'], // Mon/Wed/Thu (Owais), Tue/Fri outlier (Fahad)
    ['class' => 'Class 12A', 'period' => 7, 'source_day' => 'Monday'], // Mon/Wed/Thu (Fahad), Tue/Fri outlier (Owais)
    ['class' => 'Class 9C', 'period' => 6, 'source_day' => 'Tuesday'], // Majority Tue-Fri (Amjad), Mon outlier (Shahjahan + Amjad)
];

echo "Applying Schedule Fixes...\n";

foreach ($fixes as $fix) {
    $class = Classes::where('name', $fix['class'])->first();
    if (!$class) {
        echo "Class {$fix['class']} not found.\n";
        continue;
    }

    // 1. Get the Correct Source Record
    $sourceRecord = Timetable::where('class_id', $class->id)
        ->where('period_no', $fix['period'])
        ->where('day', $fix['source_day'])
        ->first();

    if (!$sourceRecord) {
        echo "Source record for {$fix['class']} Period {$fix['period']} on {$fix['source_day']} not found.\n";
        continue;
    }

    // 2. Find Incorrect Records (Same Class/Period, Different Teacher/Subject)
    // We update ALL other days to match source (except valid splits which we filtered out in audit, 
    // but here we are targeting specific known bad slots)
    
    $updated = Timetable::where('class_id', $class->id)
        ->where('period_no', $fix['period'])
        ->where('day', '!=', $fix['source_day'])
        ->where(function ($query) use ($sourceRecord) {
            $query->where('teacher_id', '!=', $sourceRecord->teacher_id)
                  ->orWhere('subject_id', '!=', $sourceRecord->subject_id);
        })
        ->update([
            'teacher_id' => $sourceRecord->teacher_id,
            'subject_id' => $sourceRecord->subject_id
        ]);

    echo "Fixed {$fix['class']} Period {$fix['period']}: Updated $updated records to match {$sourceRecord->teacher->name} ({$sourceRecord->subject->name}).\n";
}

echo "Done.\n";
