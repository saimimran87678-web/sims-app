<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TeacherAttendance;
use App\Models\Substitution;
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

        // Get Substitutions
        $substitutions = Substitution::with(['timetable.class', 'timetable.subject', 'timetable.subject2', 'substituteTeacher', 'teacherAttendance.teacher'])
            ->where('date', $date)
            ->get();

        // Separate Absentees/Leaves from present
        $absentees = $attendances->whereIn('status', ['absent', 'leave']);
        
        $data = [
            'date' => $formattedDate,
            'attendances' => $attendances,
            'substitutions' => $substitutions,
            'absentees' => $absentees,
        ];

        // DEBUGGING: Return HTML instead of PDF to check if View works
        // return view('admin.teacher-attendance.pdf', $data);
        
        // $pdf = Pdf::loadView('admin.teacher-attendance.pdf', $data);
        // return $pdf->download("Teacher_Arrangement_{$date}.pdf");

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.teacher-attendance.pdf', $data);
        return $pdf->download("Teacher_Arrangement_{$date}.pdf");
    }
}
