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



        $instituteName = \App\Models\Setting::get('institute_name', 'SIMS');
        $instituteAddress = \App\Models\Setting::get('institute_address', '');
        $institutePhone = \App\Models\Setting::get('institute_phone', '');
        $instituteEmail = \App\Models\Setting::get('institute_email', '');

        $pdf = Pdf::loadView('pdf.fee-receipt', [
            'payment' => $payment,
            'record' => $payment->record,
            'student' => $payment->student,
            'instituteName' => $instituteName,
            'instituteAddress' => $instituteAddress,
            'institutePhone' => $institutePhone,
            'instituteEmail' => $instituteEmail,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-' . $payment->id . '.pdf"'
        ]);
    }
}
