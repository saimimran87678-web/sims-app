<?php

$day = 'Friday'; // Or date('l') if testing today
$period = 1;

$hostClass = \App\Models\Classes::where('name', 'Class 7C')->first();
$guestClass = \App\Models\Classes::where('name', 'Class 7D')->first();

if (!$hostClass || !$guestClass) {
    echo "Classes not found.\n";
    exit;
}

echo "Merging {$guestClass->name} into {$hostClass->name} for $day Period $period\n";

// Get Host Record
$hostSchedule = \App\Models\Timetable::where('class_id', $hostClass->id)
    ->where('day', $day)
    ->where('period_no', $period)
    ->first();

if (!$hostSchedule) {
    echo "Host Schedule (7C) not found for this slot!\n";
    exit;
}

echo "Host Teacher: {$hostSchedule->teacher_id}\n";

// Get Guest Record (or create)
$guestSchedule = \App\Models\Timetable::where('class_id', $guestClass->id)
    ->where('day', $day)
    ->where('period_no', $period)
    ->first();

if (!$guestSchedule) {
    echo "Guest Schedule (7D) not found, creating...\n";
    // Simplified creation...
} else {
    echo "Updating existing Guest Schedule...\n";
    $guestSchedule->update([
        'merged_class_id' => $hostClass->id,
        'teacher_id' => $hostSchedule->teacher_id, // Sync teacher
        'room' => $hostSchedule->room,
    ]);
}

echo "Done.\n";
