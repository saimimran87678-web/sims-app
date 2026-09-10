<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$waqas = App\Models\User::where('name', 'like', '%Waqas%')->first();
echo "Waqas ID: " . $waqas->id . "\n";

$timetable = App\Models\Timetable::where('teacher_id', $waqas->id)
    ->with(['class' => function($q) { $q->withoutGlobalScopes(); }])
    ->get()
    ->groupBy('day');

foreach ($timetable as $day => $classes) {
    echo "$day:\n";
    foreach ($classes as $t) {
        echo "- Period {$t->period_no}: Class " . ($t->class ? $t->class->name : 'N/A') . "\n";
    }
}
