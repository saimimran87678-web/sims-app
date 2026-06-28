<?php

namespace App\Services;

use App\Models\FeeRecord;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Get the school code based on the current plan.
     */
    public function getSchoolCode(): string
    {
        $plan = \App\Services\LicenseStatus::getStatus()['plan'] ?? 'standard';
        $code = Setting::get('school_code', 'SCH');
        
        if ($plan !== 'premium' && empty($code)) {
            // Standard: read-only, set at activation (fallback to SCH if not set)
            $code = 'SCH';
        }
        
        return strtoupper($code);
    }

    /**
     * Generate a PDF invoice for a fee record and persist to DB.
     * 
     * @param FeeRecord $record
     * @param bool $saveToDisk Whether to save to storage or return raw string
     * @return \App\Models\FeeInvoice|string
     */
    public function generateInvoice(FeeRecord $record, bool $saveToDisk = false)
    {
        $record->load(['student', 'class', 'items', 'payments']);

        $instituteName = Setting::get('institute_name', 'SIMS');
        $instituteAddress = Setting::get('institute_address', '');
        $institutePhone = Setting::get('institute_phone', '');
        $instituteEmail = Setting::get('institute_email', '');

        // 1. Check if invoice already exists for this fee record
        $invoice = \App\Models\FeeInvoice::where('fee_record_id', $record->id)->first();

        $schoolCode = $this->getSchoolCode();
        $periodCode = substr(str_replace('-', '', $record->period), -4); // 2026-07 -> 2607

        if ($invoice) {
            $invoiceNumber = $invoice->invoice_number;
            $nextSequence = $invoice->invoice_sequence;
            $schoolCode = $invoice->school_code;
        } else {
            // Find latest sequence for this period code
            $latestInvoice = \App\Models\FeeInvoice::where('period_code', $periodCode)
                ->orderBy('invoice_sequence', 'desc')
                ->first();
                
            $nextSequence = $latestInvoice ? $latestInvoice->invoice_sequence + 1 : 1;
            $invoiceNumber = sprintf("%s-%s-%03d", $schoolCode, $periodCode, $nextSequence);
        }

        // 2. Generate PDF
        $pdf = Pdf::loadView('pdf.fee-invoice', [
            'record' => $record,
            'student' => $record->student,
            'instituteName' => $instituteName,
            'instituteAddress' => $instituteAddress,
            'institutePhone' => $institutePhone,
            'instituteEmail' => $instituteEmail,
            'invoiceNumber' => $invoiceNumber
        ]);

        $pdfPath = $invoice ? $invoice->pdf_path : null;
        if ($saveToDisk) {
            $fileName = "invoices/{$invoiceNumber}.pdf";
            Storage::disk('public')->put($fileName, $pdf->output());
            $pdfPath = "public/{$fileName}";
        }

        // 3. Persist to DB (Update or Create)
        $invoiceData = [
            'fee_record_id' => $record->id,
            'student_id' => $record->student_id,
            'invoice_number' => $invoiceNumber,
            'invoice_sequence' => $nextSequence,
            'school_code' => $schoolCode,
            'period_code' => $periodCode,
            'student_name' => $record->student->first_name . ' ' . $record->student->last_name,
            'roll_number' => $record->student->roll_number,
            'admission_number' => $record->student->admission_number,
            'parent_phone' => $record->student->phone,
            'class_name' => $record->class->name,
            'invoice_data' => [
                'total_amount' => $record->total_amount,
                'paid_amount' => $record->paid_amount,
                'balance' => $record->balance,
                'due_date' => $record->due_date->format('Y-m-d'),
                'items' => $record->items->map(fn($item) => [
                    'name' => $item->fee_head_name,
                    'subject' => $item->subject_name,
                    'amount' => $item->amount
                ])->toArray(),
            ],
            'pdf_path' => $pdfPath,
        ];

        if ($invoice) {
            $invoice->update($invoiceData);
        } else {
            $invoice = \App\Models\FeeInvoice::create($invoiceData);
        }

        if ($saveToDisk) {
            return storage_path("app/{$pdfPath}");
        }

        return $pdf->output();
    }
}
