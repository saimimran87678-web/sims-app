<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Scanning for True Duplicates (Same Teacher, Day, Period, Class, Subject)...\n";

$duplicates = DB::table('timetables')
    ->select('teacher_id', 'day', 'period_no', 'class_id', 'subject_id', DB::raw('count(*) as count'))
    ->groupBy('teacher_id', 'day', 'period_no', 'class_id', 'subject_id')
    ->having('count', '>', 1)
    ->get();

$deletedCount = 0;

foreach ($duplicates as $group) {
    echo "Found duplicate group: Teacher {$group->teacher_id}, Day {$group->day}, Period {$group->period_no}, Class {$group->class_id}, Count {$group->count}\n";

    // Get all IDs for this group
    $ids = DB::table('timetables')
        ->where('teacher_id', $group->teacher_id)
        ->where('day', $group->day)
        ->where('period_no', $group->period_no)
        ->where('class_id', $group->class_id)
        ->where('subject_id', $group->subject_id)
        ->orderBy('id', 'asc')
        ->pluck('id')
        ->toArray();

    // Keep the first one, delete the rest
    $keepId = array_shift($ids); // Removes first element and returns it
    
    if (!empty($ids)) {
        echo " - Keeping ID: $keepId\n";
        echo " - Deleting IDs: " . implode(', ', $ids) . "\n";
        
        DB::table('timetables')->whereIn('id', $ids)->delete();
        $deletedCount += count($ids);
    }
}

echo "\nTotal rows deleted: $deletedCount\n";
