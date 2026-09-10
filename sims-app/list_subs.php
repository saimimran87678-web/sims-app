<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$date = '2026-02-10';
$periodNo = 2;

$subs = DB::table('substitutions')
    ->join('timetables', 'substitutions.timetable_id', '=', 'timetables.id')
    ->join('users as sub_teacher', 'substitutions.substitute_teacher_id', '=', 'sub_teacher.id')
    ->join('users as abs_teacher', 'timetables.teacher_id', '=', 'abs_teacher.id')
    ->where('substitutions.date', $date)
    ->where('timetables.period_no', $periodNo)
    ->select('sub_teacher.name as substitute', 'abs_teacher.name as absent', 'timetables.id as tt_id')
    ->get();

echo "Substitutions for P" . $periodNo . " on " . $date . ":\n";
foreach ($subs as $s) {
    echo "- " . $s->substitute . " is covering for " . $s->absent . " (TT ID: " . $s->tt_id . ")\n";
}
