<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Classes;
use App\Models\User;
use App\Models\Timetable;
use App\Models\ClosedClassroom;

// 1. Find Class 10A
$classes = Classes::where('name', 'LIKE', '%10A%')->get();
echo "Found " . $classes->count() . " classes matching '10A':\n";
foreach ($classes as $c) {
    echo " - ID: {$c->id}, Name: {$c->name}\n";
}
$class = $classes->first(); // Use the first one for further checks

// 2. Find Mr. Safeerullah
$teachers = User::where('name', 'LIKE', '%Safeerullah%')->get();
echo "Found " . $teachers->count() . " teachers matching 'Safeerullah':\n";
foreach ($teachers as $t) {
    echo " - ID: {$t->id}, Name: {$t->name}\n";
}
$teacher = $teachers->first();

// 3. Check ClosedClassroom table
$today = date('Y-m-d'); // Assuming test is for today, but user might have selected a different date in the UI.
// Let's check ALL closed classrooms for this class
$closures = ClosedClassroom::where('class_id', $class->id)->get();
echo "Closures for 10A:\n";
foreach ($closures as $c) {
    echo " - Date: {$c->date}, Reason: {$c->reason}\n";
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
foreach ($days as $d) {
    echo "\n--- $d ---\n";
    $periods = Timetable::where('teacher_id', $teacher->id)
        ->where('day', $d)
        ->whereHas('template', fn($q) => $q->where('is_active', true))
        ->orderBy('period_no')
        ->with('class')
        ->get();
    
    foreach ($periods as $p) {
        echo "Period {$p->period_no}: {$p->class->name}\n";
    }
}

// 4. Check Timetable for today (or recent dates)
// We need to know what 'day' it is effectively.
// Let's assume the user is working on 'today' or the date in the screenshot.
// The user said "being closed for today".
echo "Today is: $today (" . date('l') . ")\n";

$timetables = Timetable::where('teacher_id', $teacher->id)
    ->where('class_id', $class->id)
    ->whereHas('template', fn($q) => $q->where('is_active', true))
    ->get();

echo "Timetables for Safeerullah + 10A:\n";
foreach ($timetables as $t) {
    echo " - Day: {$t->day}, Period: {$t->period_no}, ClassID: {$t->class_id}\n";
}

// 5. Test the Query Logic
$dateToCheck = $today; // Or maybe the user set a different date?
$closedIds = ClosedClassroom::where('date', $dateToCheck)->pluck('class_id')->toArray();
echo "Closed Class IDs for $dateToCheck: " . implode(',', $closedIds) . "\n";

if (in_array($class->id, $closedIds)) {
    echo "VERDICT: Class 10A IS in the closed list for today.\n";
} else {
    echo "VERDICT: Class 10A IS NOT in the closed list for today.\n";
}
