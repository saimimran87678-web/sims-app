<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classId = 23; // Class 9C
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach ($days as $day) {
    echo "$day:\n";
    $timetable = App\Models\Timetable::where('class_id', $classId)
        ->where('day', $day)
        ->with('teacher', 'subject')
        ->orderBy('period_no')
        ->get();
    
    foreach ($timetable as $t) {
        $teacherName = $t->teacher ? $t->teacher->name : 'N/A';
        $subjectName = $t->subject ? $t->subject->name : 'N/A';
        echo "  Period {$t->period_no}: {$subjectName} ({$teacherName})\n";
    }
}
