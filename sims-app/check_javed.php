<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\TeacherAttendance;
use App\Models\Timetable;
use Illuminate\Support\Facades\DB;

$teacherId = 25;
$date = '2026-02-10';
$day = 'Tuesday';
$periodNo = 2;

$teacher = User::find($teacherId);
$attendance = TeacherAttendance::where('user_id', $teacherId)->where('date', $date)->first();
$timetable = Timetable::where('teacher_id', $teacherId)
    ->where('day', $day)
    ->where('period_no', $periodNo)
    ->whereHas('template', fn($q) => $q->where('is_active', true))
    ->first();

$subst = DB::table('substitutions')
    ->join('timetables', 'substitutions.timetable_id', '=', 'timetables.id')
    ->where('substitutions.date', $date)
    ->where('timetables.period_no', $periodNo)
    ->where('substitutions.substitute_teacher_id', $teacherId)
    ->first();

echo "Teacher: " . ($teacher ? $teacher->name : 'Not Found') . "\n";
echo "Attendance Status: " . ($attendance ? $attendance->status : 'No Record') . "\n";
echo "Has Class at P" . $periodNo . ": " . ($timetable ? "Yes (" . $timetable->class->name . ")" : "No") . "\n";
echo "Is already a substitute for P" . $periodNo . ": " . ($subst ? "Yes (Timetable ID: " . $subst->id . ")" : "No") . "\n";
