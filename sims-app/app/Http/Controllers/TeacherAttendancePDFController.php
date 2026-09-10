<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TeacherAttendance;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherAttendancePDFController extends Controller
{
    public function download(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $formattedDate = Carbon::parse($date)->format('l, d M Y');

        // Fetch Data
        $attendances = TeacherAttendance::with(['teacher'])
            ->where('date', $date)
            ->get();

        // Separate Absentees/Leaves/OD/SL from present
        $absentees = $attendances->whereIn('status', ['absent', 'leave', 'short_leave', 'official_duty']);

        // Fetch Substitutions for this date
        $substitutions = \Illuminate\Support\Facades\DB::table('substitutions')
            ->leftJoin('timetables', 'substitutions.timetable_id', '=', 'timetables.id')
            ->leftJoin('classes', 'timetables.class_id', '=', 'classes.id')
            ->leftJoin('subjects', 'timetables.subject_id', '=', 'subjects.id')
            ->leftJoin('users as substitute_teacher', 'substitutions.substitute_teacher_id', '=', 'substitute_teacher.id')
            ->where('substitutions.date', $date)
            ->whereNotIn('timetables.class_id', \App\Models\ClosedClassroom::where('date', $date)->pluck('class_id'))
            ->select(
                'substitutions.*',
                'timetables.period_no',
                'timetables.teacher_id as absent_teacher_id', // original teacher
                'classes.name as class_name',
                'subjects.name as subject_name',
                'substitute_teacher.name as substitute_name'
            )
            ->orderBy('timetables.period_no')
            ->get();
        
        // Group substitutions by Absent Teacher ID for easy display
        $groupedSubstitutions = $substitutions->groupBy('absent_teacher_id')->map(function($teacherSubs) use ($date) {
            return $teacherSubs->groupBy('period_no')->map(function($periodSubs) use ($date) {
                $first = $periodSubs->first();
                
                // Format names
                $names = $periodSubs->pluck('class_name')->toArray();
                $className = $this->formatCombinedClassNames($names);

                return (object)[
                    'period_no' => $first->period_no,
                    'class_name' => $className,
                    'subject_name' => $periodSubs->pluck('subject_name')->unique()->implode(' / '),
                    'substitute_name' => $first->substitute_name
                ];
            });
        });

        // Filter absentees: Show ALL non-present teachers (absent/leave/OD/SL),
        // with or without substitutions, so OD/SL classes that still need cover are visible.
        // Present teachers must never appear in this report, even if they have substitutions assigned.
        $absentees = $attendances->filter(function($record) {
            return in_array($record->status, ['absent', 'leave', 'official_duty', 'short_leave']);
        });

        // Fetch daily substitution counts for all teachers involved
        $substitutionCounts = \Illuminate\Support\Facades\DB::table('substitutions')
            ->where('date', $date)
            ->groupBy('substitute_teacher_id')
            ->select('substitute_teacher_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->pluck('count', 'substitute_teacher_id')
            ->toArray();

        // Fetch Period Labels from active template
        $activeTemplate = \App\Models\ScheduleTemplate::where('is_active', true)->first();
        $periodLabels = [];
        if ($activeTemplate) {
            $periodLabels = \App\Models\PeriodConfig::where('schedule_template_id', $activeTemplate->id)
                ->pluck('label', 'period_no')
                ->toArray();
        }
        
        $data = [
            'date' => $formattedDate,
            'attendances' => $attendances,
            'absentees' => $absentees,
            'groupedSubstitutions' => $groupedSubstitutions,
            'periodLabels' => $periodLabels,
            'substitutionCounts' => $substitutionCounts,
            'dailyNote' => \App\Models\DailyNote::where('date', $date)->value('note'),
        ];

        // DEBUGGING: Return HTML instead of PDF to check if View works
        // return view('admin.teacher-attendance.pdf', $data);
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.teacher-attendance.pdf', $data);
        return $pdf->download("Teacher_Arrangement_{$date}.pdf");
    }

    private function formatCombinedClassNames($names)
    {
        if (count($names) <= 1) return str_replace('Class ', '', $names[0] ?? '');
        
        $cleanedNames = array_map(fn($n) => str_replace('Class ', '', $n), $names);
        
        $prefixes = [];
        $suffixes = [];
        
        foreach ($cleanedNames as $name) {
            if (preg_match('/^(\d+)(.*)$/', $name, $matches)) {
                $prefixes[] = $matches[1];
                $suffixes[] = $matches[2];
            } else {
                return implode('+', $cleanedNames);
            }
        }
        
        if (count(array_unique($prefixes)) === 1) {
            return $prefixes[0] . '(' . implode('+', $suffixes) . ')';
        }
        
        return implode('+', $cleanedNames);
    }
}
