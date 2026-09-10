<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TeacherAttendance;
use Illuminate\Support\Facades\DB;

$date = '2026-02-10';

echo "Data for Date: $date\n";
echo "---------------------------------\n";

$attendanceCount = TeacherAttendance::where('date', $date)->count();
echo "Total Attendance Records: $attendanceCount\n";

$absentees = TeacherAttendance::where('date', $date)
    ->whereIn('status', ['absent', 'leave', 'short_leave', 'official_duty'])
    ->get();
echo "Absent/Leave/OD/SL Teachers: " . $absentees->count() . "\n";
foreach ($absentees as $abs) {
    echo "- User ID " . $abs->user_id . " (" . $abs->status . ")\n";
}

$substitutions = DB::table('substitutions')->where('date', $date)->get();
echo "Total Substitution Records: " . $substitutions->count() . "\n";
foreach ($substitutions as $sub) {
    echo "- TT ID " . $sub->timetable_id . " (Absent: " . $sub->absent_teacher_id . " -> Sub: " . $sub->substitute_teacher_id . ")\n";
}

// Check for orphaned substitutions (timetable_id not in timetables table)
$orphaned = DB::table('substitutions')
    ->leftJoin('timetables', 'substitutions.timetable_id', '=', 'timetables.id')
    ->where('substitutions.date', $date)
    ->whereNull('timetables.id')
    ->count();

echo "Orphaned Substitutions (missing timetable): $orphaned\n";
