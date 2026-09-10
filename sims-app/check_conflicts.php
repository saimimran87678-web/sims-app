<?php

use Illuminate\Support\Facades\DB;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Scanning for Teacher Schedule Conflicts (Same Teacher, Day, Period, DIFFERENT Class/Subject)...\n";

$conflicts = DB::table('timetables')
    ->select('teacher_id', 'day', 'period_no', DB::raw('count(*) as count'))
    ->groupBy('teacher_id', 'day', 'period_no')
    ->having('count', '>', 1)
    ->get();

foreach ($conflicts as $c) {
    echo "\nConflict: Teacher {$c->teacher_id}, Day {$c->day}, Period {$c->period_no}, Count {$c->count}\n";
    
    $teacher = User::find($c->teacher_id);
    echo "Teacher Name: " . ($teacher ? $teacher->name : 'Unknown') . "\n";

    $rows = DB::table('timetables')
        ->join('classes', 'timetables.class_id', '=', 'classes.id')
        ->join('subjects', 'timetables.subject_id', '=', 'subjects.id')
        ->where('teacher_id', $c->teacher_id)
        ->where('day', $c->day)
        ->where('period_no', $c->period_no)
        ->select('timetables.id', 'classes.name as class', 'subjects.name as subject', 'timetables.created_at')
        ->get();
        
    foreach($rows as $r) {
        echo "   - ID: {$r->id}, Class: {$r->class}, Subject: {$r->subject}\n";
    }
}
