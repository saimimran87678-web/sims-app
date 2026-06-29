<?php

namespace App\Http\Controllers;

use App\Models\FeeRecord;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class PublicVoucherController extends Controller
{
    /**
     * Show the public voucher and payment status page.
     */
    public function show(string $token)
    {
        $record = FeeRecord::with(['student', 'class', 'items', 'payments'])
            ->where('access_token', $token)
            ->firstOrFail();

        // Calculate previous outstanding arrears
        $previousArrears = FeeRecord::where('student_id', $record->student_id)
            ->where('status', '!=', 'paid')
            ->where('period', '<', $record->period)
            ->sum('balance');

        $instituteName = \App\Models\Setting::get('institute_name', 'SIMS');
        $instituteAddress = \App\Models\Setting::get('institute_address', '');
        $institutePhone = \App\Models\Setting::get('institute_phone', '');
        $instituteEmail = \App\Models\Setting::get('institute_email', '');

        // Generate invoice number structure
        $invoice = \App\Models\FeeInvoice::where('fee_record_id', $record->id)->first();
        
        $schoolCode = 'SCH';
        if ($invoice) {
            $schoolCode = $invoice->school_code;
        } else {
            $invoiceService = app(InvoiceService::class);
            $schoolCode = $invoiceService->getSchoolCode();
        }

        $periodCode = substr(str_replace('-', '', $record->period), -4); // 2607
        
        if ($invoice) {
            $invoiceNumber = $invoice->invoice_number;
        } else {
            // Predict sequence dynamically if not persisted yet
            $latestInvoice = \App\Models\FeeInvoice::where('period_code', $periodCode)
                ->orderBy('invoice_sequence', 'desc')
                ->first();
            $nextSequence = $latestInvoice ? $latestInvoice->invoice_sequence + 1 : 1;
            $invoiceNumber = sprintf("%s-%s-%03d", $schoolCode, $periodCode, $nextSequence);
        }

        return view('public.fee-challan', [
            'record' => $record,
            'student' => $record->student,
            'class' => $record->class,
            'previousArrears' => $previousArrears,
            'instituteName' => $instituteName,
            'instituteAddress' => $instituteAddress,
            'institutePhone' => $institutePhone,
            'instituteEmail' => $instituteEmail,
            'invoiceNumber' => $invoiceNumber,
            'token' => $token,
        ]);
    }

    /**
     * Download the PDF invoice on demand.
     */
    public function downloadPdf(string $token, InvoiceService $invoiceService)
    {
        $record = FeeRecord::where('access_token', $token)->firstOrFail();

        $pdfContent = $invoiceService->generateInvoice($record, false);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-' . $record->id . '.pdf"'
        ]);
    }
}
