<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teacherId = 5;
$teacher = App\Models\User::find($teacherId);
echo "Teacher: " . $teacher->name . "\n";

$timetable = App\Models\Timetable::where('teacher_id', $teacherId)
    ->with([
        'class' => function($q) { $q->withoutGlobalScopes(); }, 
        'subject'
    ])
    ->orderBy('day')
    ->orderBy('period_no')
    ->get()
    ->map(function($t) {
        return [
            'id' => $t->id,
            'day' => $t->day,
            'period' => $t->period_no,
            'class' => $t->class ? $t->class->name : 'N/A',
            'subject' => $t->subject ? $t->subject->name : 'N/A'
        ];
    })->toArray();

echo json_encode($timetable, JSON_PRETTY_PRINT);