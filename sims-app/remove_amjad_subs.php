<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$namePart = 'Muhammad Amjad';
$date = '2026-01-26'; // Monday
$dayName = 'Monday';

echo "Processing Absent Teacher: $namePart...\n";

// 1. Find the Absent Teacher
$absentTeacher = DB::table('users')->where('name', 'LIKE', "%$namePart%")->first();

if (!$absentTeacher) {
    echo "  - Teacher not found!\n";
    exit;
}
echo "  - Identified ID: {$absentTeacher->id} ({$absentTeacher->name})\n";

// 2. Find their Regular Schedule for Monday
$regularSlots = DB::table('timetables')
    ->where('teacher_id', $absentTeacher->id)
    ->where('day', $dayName)
    ->where('is_substitute', false)
    ->get();
    
if ($regularSlots->isEmpty()) {
    echo "  - No regular classes found for this teacher on $dayName.\n";
    exit;
}

echo "  - Found " . $regularSlots->count() . " regular classes.\n";

// 3. Find Substitutions covering these slots
$deletedCount = 0;
foreach ($regularSlots as $slot) {
    $subRecord = DB::table('timetables')
        ->where('class_id', $slot->class_id)
        ->where('period_no', $slot->period_no)
        ->where('substitute_date', $date)
        ->where('is_substitute', true)
        ->first();
        
    if ($subRecord) {
            $subTeacher = DB::table('users')->where('id', $subRecord->teacher_id)->value('name');
            echo "    - Found Substitution: Period {$slot->period_no} (Class ID {$slot->class_id}) covered by $subTeacher. Deleting...\n";
            
            DB::table('timetables')->where('id', $subRecord->id)->delete();
            $deletedCount++;
    }
}

if ($deletedCount > 0) {
    echo "  - Successfully removed $deletedCount substitution records.\n";
} else {
    echo "  - No substitutions found for his classes on this date.\n";
}
