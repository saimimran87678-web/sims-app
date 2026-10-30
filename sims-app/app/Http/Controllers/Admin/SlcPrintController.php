<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolLeavingCertificate;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SlcPrintController extends Controller
{
    public function __invoke(Request $request, SchoolLeavingCertificate $certificate)
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->role === 'admin' || $user->hasRole('Super Admin'));
        if (!$isAdmin) {
            abort_if(!$user || (!$user->can('student.view') && !$user->can('student.edit')), 403);
        }

        $certificate->load([
            'student.section',
            'academicSession',
            'enrollment.classModel',
            'issuer'
        ]);

        $instituteName = Setting::getFormalInstituteName();
        $instituteAddress = Setting::getGlobal('institute_address', '');
        $institutePhone = Setting::getGlobal('institute_phone', '');
        $instituteEmail = Setting::getGlobal('institute_email', '');

        $logoPath = Setting::getGlobal('institute_logo', '');
        $logoBase64 = null;
        if ($logoPath && file_exists(public_path($logoPath)) && extension_loaded('gd')) {
            $type = pathinfo(public_path($logoPath), PATHINFO_EXTENSION);
            $data = file_get_contents(public_path($logoPath));
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        ActivityLogger::logPrint(
            type: 'School Leaving Certificate',
            description: "Printed SLC #{$certificate->certificate_number} for {$certificate->student->name}",
            details: [
                'certificate_number' => $certificate->certificate_number,
                'student_id' => $certificate->student_id,
                'student_name' => $certificate->student->name,
                'leaving_date' => $certificate->leaving_date->format('Y-m-d'),
                'reason' => $certificate->reason,
            ]
        );

        $viewData = [
            'certificate' => $certificate,
            'student' => $certificate->student,
            'enrollment' => $certificate->enrollment,
            'session' => $certificate->academicSession,
            'instituteName' => $instituteName,
            'instituteAddress' => $instituteAddress,
            'institutePhone' => $institutePhone,
            'instituteEmail' => $instituteEmail,
            'instituteLogo' => $logoBase64,
        ];

        if ($request->query('format') === 'html') {
            return view('pdf.school-leaving-certificate', $viewData);
        }

        $pdf = Pdf::loadView('pdf.school-leaving-certificate', $viewData)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'defaultFont' => 'sans-serif'
            ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="SLC-' . $certificate->certificate_number . '.pdf"'
        ]);
    }
}
