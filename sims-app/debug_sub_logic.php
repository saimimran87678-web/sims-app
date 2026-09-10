<?php

use App\Models\User;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Pick a teacher who is absent or just any teacher
$teacher = User::where('role', 'teacher')->first();

if (!$teacher) {
    echo "No teachers found.\n";
    exit;
}

echo "Testing for Teacher: " . $teacher->name . " (ID: " . $teacher->id . ")\n";

// 2. Simulate Date and Day
$date = Carbon::now()->format('Y-m-d'); // Or hardcode a Monday if needed
$dayOfWeek = Carbon::parse($date)->format('l');

echo "Date: $date, Day: $dayOfWeek\n";

// 3. Query Timetable (Replicating Logic)
$query = Timetable::with(['class', 'subject', 'template'])
    ->where('teacher_id', $teacher->id)
    ->where('day', $dayOfWeek);

// Check count before template filter
echo "Classes found before template filter: " . $query->count() . "\n";

$query->whereHas('template', function($q) {
    $q->where('is_active', true);
});

$todaysClasses = $query->orderBy('period_no')->get();

echo "Classes found AFTER template filter: " . $todaysClasses->count() . "\n";

if ($todaysClasses->isEmpty()) {
    echo "Reason: No classes found. Checking Active Templates...\n";
    $activeTemplates = \App\Models\ScheduleTemplate::where('is_active', true)->get();
    echo "Active Templates Count: " . $activeTemplates->count() . "\n";
    foreach($activeTemplates as $t) {
        echo " - ID: " . $t->id . ", Name: " . $t->name . "\n";
    }
    
    // Check if teacher has ANY classes on any day
    $anyClasses = Timetable::where('teacher_id', $teacher->id)->count();
    echo "Total classes for this teacher in DB (any day): $anyClasses\n";
} else {
    foreach($todaysClasses as $c) {
        echo " - Period " . $c->period_no . ": " . $c->class->name . " (" . $c->subject->name . ")\n";
    }
}
