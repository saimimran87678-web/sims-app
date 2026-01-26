<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check all Period 1 entries for Class 10B to see day values
$entries = DB::table('timetables')
    ->where('class_id', 1)
    ->where('period_no', 1)
    ->where('is_substitute', false)
    ->get();

echo "Period 1 entries for Class 10B:\n\n";
foreach ($entries as $entry) {
    echo "  ID: {$entry->id}, Day: {$entry->day}, is_divided: {$entry->is_divided}\n";
}

echo "\n\nPeriod 6 entries for Class 10B (the divided period):\n\n";
$entries6 = DB::table('timetables')
    ->where('class_id', 1)
    ->where('period_no', 6)
    ->where('is_substitute', false)
    ->get();

foreach ($entries6 as $entry) {
    $subject = DB::table('subjects')->where('id', $entry->subject_id)->value('name');
    $teacher = DB::table('users')->where('id', $entry->teacher_id)->value('name');
    echo "  ID: {$entry->id}, Day: {$entry->day}, Subject: {$subject}, Teacher: {$teacher}, is_divided: {$entry->is_divided}\n";
}
