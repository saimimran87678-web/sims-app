<?php

namespace App\Livewire\Admin\Reports;

use Livewire\Component;
use App\Models\TeacherAttendance;
use App\Models\Substitution;
use App\Models\PeriodConfig;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherArrangementReport extends Component
{
    public $date;

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    public function downloadPdf()
    {
        $date = $this->date;
        $formattedDate = Carbon::parse($date)->format('l, d M Y');
        $dayName = Carbon::parse($date)->format('l');

        // Fetch Absentees
        $attendances = TeacherAttendance::with(['teacher'])
            ->where('date', $date)
            ->whereIn('status', ['absent', 'leave', 'late', 'official_duty'])
            ->get();

        $reportData = [];

        foreach ($attendances as $attendance) {
            // Get Teacher's Schedule for the day
            $schedules = \App\Models\Timetable::with(['class', 'subject', 'subject2'])
                ->where('teacher_id', $attendance->user_id)
                ->where('day', $dayName)
                ->orderBy('period_no')
                ->get();

            // Get Assigned Substitutions
            $substitutions = Substitution::where('teacher_attendance_id', $attendance->id)
                ->get()
                ->keyBy('timetable_id');

            // Fetch Substitution Counts for the Day
            $subCounts = Substitution::where('date', $date)
                ->select('substitute_teacher_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('substitute_teacher_id')
                ->pluck('total', 'substitute_teacher_id');

            $teacherSchedule = [];
            foreach ($schedules as $schedule) {
                $subRecord = $substitutions->get($schedule->id);
                $subTeacher = $subRecord ? \App\Models\User::find($subRecord->substitute_teacher_id) : null;

                // Format Name with Count
                $subName = 'Not Assigned';
                if ($subTeacher) {
                    $count = $subCounts[$subTeacher->id] ?? 0;
                    $subName = $subTeacher->name . " ($count)";
                }

                $teacherSchedule[] = [
                    'period' => $schedule->period_no, // PDF might use basic period no or I can duplicate label logic if needed. keeping basic for now as previously invoked
                    'class' => $schedule->class->name,
                    'subject' => $schedule->subject->name . ($schedule->subject2 ? ' / ' . $schedule->subject2->name : ''),
                    'substitute' => $subName,
                ];
            }

            $reportData[] = [
                'teacher' => $attendance->teacher->name,
                'status' => $attendance->status,
                'remarks' => $attendance->remarks,
                'schedule' => $teacherSchedule,
            ];
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.reports.arrangement-pdf', [
            'date' => $formattedDate,
            'reportData' => $reportData
        ]);
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "Teacher_Arrangement_{$date}.pdf");
    }

    public function render()
    {
        $formattedDate = Carbon::parse($this->date)->format('l, d M Y');
        $dayName = Carbon::parse($this->date)->format('l');

        // Fetch Absentees
        $attendances = TeacherAttendance::with(['teacher'])
            ->where('date', $this->date)
            ->whereIn('status', ['absent', 'leave', 'late', 'official_duty'])
            ->get();

        $reportData = [];

        foreach ($attendances as $attendance) {
            // Get Teacher's Schedule for the day
            $schedules = \App\Models\Timetable::with(['class', 'subject', 'subject2'])
                ->where('teacher_id', $attendance->user_id)
                ->where('day', $dayName)
                ->orderBy('period_no')
                ->get();

            // Get Assigned Substitutions
            $substitutions = Substitution::where('teacher_attendance_id', $attendance->id)
                ->get()
                ->keyBy('timetable_id');

            // Fetch Period Configs for this teacher's template(s)
            $templateIds = $schedules->pluck('schedule_template_id')->unique();
            $configs = PeriodConfig::whereIn('schedule_template_id', $templateIds)->get();
            $periodLabels = [];
            foreach ($configs as $config) {
                $periodLabels[$config->schedule_template_id . '_' . $config->period_no] = $config->label;
            }

            // Fetch Substitution Counts for the Day
            $subCounts = Substitution::where('date', $this->date)
                ->select('substitute_teacher_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('substitute_teacher_id')
                ->pluck('total', 'substitute_teacher_id');

            $teacherSchedule = [];
            foreach ($schedules as $schedule) {
                $subRecord = $substitutions->get($schedule->id);
                $subTeacher = $subRecord ? \App\Models\User::find($subRecord->substitute_teacher_id) : null;
                
                // Determine Label
                $label = $periodLabels[$schedule->schedule_template_id . '_' . $schedule->period_no] ?? $schedule->period_no;
                $displayLabel = str_replace('Period ', '', $label);

                // Format Name with Count
                $subName = 'Not Assigned';
                if ($subTeacher) {
                    $count = $subCounts[$subTeacher->id] ?? 0;
                    $subName = $subTeacher->name . " ($count)";
                }

                $teacherSchedule[] = [
                    'period' => $displayLabel, 
                    'class' => $schedule->class->name,
                    'subject' => $schedule->subject->name . ($schedule->subject2 ? ' / ' . $schedule->subject2->name : ''),
                    'substitute' => $subName,
                    'sub_teacher_id' => $subTeacher ? $subTeacher->id : null,
                ];
            }

            $reportData[] = [
                'teacher' => $attendance->teacher->name,
                'teacher_id' => $attendance->teacher->id,
                'status' => $attendance->status,
                'remarks' => $attendance->remarks,
                'schedule' => $teacherSchedule,
            ];
        }

        return view('livewire.admin.reports.teacher-arrangement-report', [
            'reportData' => $reportData,
            'formattedDate' => $formattedDate,
        ])->layout('components.layouts.admin', ['title' => 'Teacher Arrangement Report']);
    }
}
