<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Timetable;
use App\Models\DailyClassMerge;
use App\Models\ClosedClassroom;

$teacherId = 20;
$dayOfWeek = 'Monday';
$date = '2026-02-23';

echo "Timetable for Mr. Aftab (Teacher ID: $teacherId) on $dayOfWeek:\n";
$timetables = Timetable::with(['class', 'subject', 'template'])
    ->where('teacher_id', $teacherId)
    ->where('day', $dayOfWeek)
    ->whereHas('template', function($q) {
        $q->where('is_active', true);
    })
    ->orderBy('period_no')
    ->get();

foreach ($timetables as $t) {
    echo "ID: {$t->id}, Period: {$t->period_no}, Class: {$t->class->name}, Subject: {$t->subject->name}\n";
}

echo "\nDaily Merges for $date:\n";
$dailyMerges = DailyClassMerge::where('date', $date)->get();
foreach ($dailyMerges as $merge) {
    echo "Source: {$merge->source_timetable_id}, Target: {$merge->target_timetable_id}\n";
}

echo "\nClosed Classrooms for $date:\n";
$closedClasses = ClosedClassroom::where('date', $date)->get();
foreach ($closedClasses as $cc) {
    echo "Class ID: {$cc->class_id}, Reason: {$cc->reason}\n";
}
