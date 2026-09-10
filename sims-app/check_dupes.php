<?php

use App\Models\ScheduleTemplate;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$activeTemplates = ScheduleTemplate::where('is_active', true)->get();

echo "Active Templates Count: " . $activeTemplates->count() . "\n";

// Check for duplicates in Timetables 
$dupes = DB::table('timetables')
    ->select('teacher_id', 'day', 'period_no', 'schedule_template_id', DB::raw('count(*) as count'))
    ->groupBy('teacher_id', 'day', 'period_no', 'schedule_template_id')
    ->having('count', '>', 1)
    ->get();

echo "\nDuplicate Timetable Entries:\n";
foreach($dupes as $d) {
    echo "Teacher ID: " . $d->teacher_id . ", Day: " . $d->day . ", Period: " . $d->period_no . ", Template: " . $d->schedule_template_id . ", Count: " . $d->count . "\n";
    
    // Fetch the actual IDs of the duplicates to see if they are distinct rows
    $rows = DB::table('timetables')
        ->where('teacher_id', $d->teacher_id)
        ->where('day', $d->day)
        ->where('period_no', $d->period_no)
        ->where('schedule_template_id', $d->schedule_template_id)
        ->get();
        
    foreach($rows as $r) {
        echo "   - Row ID: " . $r->id . ", Subject: " . $r->subject_id . ", Class: " . $r->class_id . "\n";
    }
}
