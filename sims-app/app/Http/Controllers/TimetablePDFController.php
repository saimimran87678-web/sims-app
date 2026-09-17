<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\User;
use App\Models\ScheduleTemplate;
use App\Models\PeriodConfig;
use App\Models\Timetable;
use App\Models\TeacherDuty;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class TimetablePDFController extends Controller
{
    public function downloadClassTimetable($id)
    {
        $class = Classes::findOrFail($id);
        $activeTemplate = ScheduleTemplate::where('is_active', true)->first();
        
        if (!$activeTemplate) {
            return back()->with('error', 'No active schedule template found.');
        }

        $periods = PeriodConfig::where('schedule_template_id', $activeTemplate->id)
            ->orderBy('period_no')
            ->get();

        $timetableData = Timetable::with(['subject', 'subject2', 'teacher'])
            ->where('schedule_template_id', $activeTemplate->id)
            ->where(function($q) use ($id) {
                $q->where('class_id', $id)
                  ->orWhere('merged_class_id', $id);
            })
            ->get();

        $pdf = Pdf::loadView('admin.timetable.pdf.class', [
            'class' => $class,
            'periods' => $periods,
            'timetable' => $timetableData,
            'template' => $activeTemplate,
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ]);

        return $pdf->download("Timetable_Class_{$class->name}.pdf");
    }

    public function downloadTeacherTimetable($id)
    {
        $activeTemplate = ScheduleTemplate::where('is_active', true)->first();
        
        if (!$activeTemplate) {
            return back()->with('error', 'No active schedule template found.');
        }

        $periods = PeriodConfig::where('schedule_template_id', $activeTemplate->id)
            ->orderBy('period_no')
            ->get();

        if ($id === 'all') {
            $teachers = User::where('role', 'teacher')->orderBy('name')->get();
            $timetableData = Timetable::with(['subject', 'subject2', 'class'])
                ->where('schedule_template_id', $activeTemplate->id)
                ->get(); // Fetch all for efficiency

            $teacherDuties = TeacherDuty::where('schedule_template_id', $activeTemplate->id)->get();

            $pdf = Pdf::loadView('admin.timetable.pdf.teachers_grid', [
                'teachers' => $teachers,
                'periods' => $periods,
                'timetable' => $timetableData,
                'teacherDuties' => $teacherDuties,
                'template' => $activeTemplate,
                'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            ]);
            
            // Landscape is better for 3 columns side-by-side
            $pdf->setPaper('a4', 'landscape'); 

            return $pdf->download("All_Teachers_Timetable.pdf");
        }

        $teacher = User::findOrFail($id);
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        $timetableData = Timetable::with(['subject', 'subject2', 'class'])
            ->where('schedule_template_id', $activeTemplate->id)
            ->where('teacher_id', $id)
            ->get();

        $teacherDuties = TeacherDuty::where('schedule_template_id', $activeTemplate->id)
            ->where('teacher_id', $id)
            ->get();

        $pdf = Pdf::loadView('admin.timetable.pdf.teacher', [
            'teacher' => $teacher,
            'periods' => $periods,
            'timetable' => $timetableData,
            'teacherDuties' => $teacherDuties,
            'template' => $activeTemplate,
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ]);

        return $pdf->download("Timetable_Teacher_{$teacher->name}.pdf");
    }

    public function downloadMasterTimetable(Request $request)
    {
        $mode = $request->input('mode', 'class'); // 'class' or 'teacher'
        $day = $request->input('day');

        $activeTemplate = ScheduleTemplate::where('is_active', true)->first();
        
        if (!$activeTemplate) {
            return back()->with('error', 'No active schedule template found.');
        }

        $periods = PeriodConfig::where('schedule_template_id', $activeTemplate->id)
            ->orderBy('period_no')
            ->get();
            
        $query = Timetable::with(['subject', 'subject2', 'class', 'teacher'])
            ->where('schedule_template_id', $activeTemplate->id);
            
        if ($day) {
            $query->where('day', $day);
            $days = [$day];
        } else {
            // Fallback or "Print All Days" if needed, but user requested single day
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        }

        $timetableData = $query->get();
        $teacherDuties = TeacherDuty::where('schedule_template_id', $activeTemplate->id)->get();

        $data = [
            'periods' => $periods,
            'timetable' => $timetableData,
            'teacherDuties' => $teacherDuties,
            'template' => $activeTemplate,
            'days' => $days,
            'mode' => $mode
        ];

        if ($mode === 'class') {
            $data['items'] = Classes::orderBy('numeric_value')->orderBy('name')->get();
        } else {
             $data['items'] = User::where('role', 'teacher')->orderBy('name')->get();
        }

        $pdf = Pdf::loadView('admin.timetable.pdf.master', $data);
        
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("Master_Timetable_{$mode}_{$day}.pdf");
    }
}
