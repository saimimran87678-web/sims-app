<?php

namespace App\Http\Controllers\Admin\Fee;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DownloadReceiptController extends Controller
{
    public function __invoke(Request $request, FeePayment $payment)
    {
        // Ensure user has permission
        $user = auth()->user();
        if ($user->role !== 'admin' && !$user->hasRole('Super Admin')) {
            abort_if(!$user->can('fees.manage'), 403);
        }

        $payment->load(['student', 'record', 'record.class', 'record.items']);



        $instituteName = \App\Models\Setting::getGlobal('institute_formal_name', \App\Models\Setting::getGlobal('institute_name', 'SIMS'));
        $instituteAddress = \App\Models\Setting::getGlobal('institute_address', '');
        $institutePhone = \App\Models\Setting::getGlobal('institute_phone', '');
        $instituteEmail = \App\Models\Setting::getGlobal('institute_email', '');

        $logoPath = \App\Models\Setting::getGlobal('institute_logo', '');
        $logoBase64 = null;
        if ($logoPath && file_exists(public_path($logoPath)) && extension_loaded('gd')) {
            $type = pathinfo(public_path($logoPath), PATHINFO_EXTENSION);
            $data = file_get_contents(public_path($logoPath));
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $pdf = Pdf::loadView('pdf.fee-receipt', [
            'payment' => $payment,
            'record' => $payment->record,
            'student' => $payment->student,
            'instituteName' => $instituteName,
            'instituteAddress' => $instituteAddress,
            'institutePhone' => $institutePhone,
            'instituteEmail' => $instituteEmail,
            'instituteLogo' => $logoBase64,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-' . $payment->id . '.pdf"'
        ]);
    }
}
