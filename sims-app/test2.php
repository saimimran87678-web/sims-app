<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teacherId = 5;
$timetable = App\Models\Timetable::where('teacher_id', $teacherId)
    ->with(['class' => function($q) { $q->withoutGlobalScopes(); }])
    ->get()
    ->groupBy('day');

foreach ($timetable as $day => $classes) {
    echo "$day:\n";
    foreach ($classes as $c) {
        $className = $c->class ? $c->class->name : 'N/A';
        echo "  Period " . $c->period_no . ": " . $className . "\n";
    }
}
