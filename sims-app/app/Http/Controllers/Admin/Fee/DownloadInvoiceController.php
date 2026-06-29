<?php

namespace App\Http\Controllers\Admin\Fee;

use App\Http\Controllers\Controller;
use App\Models\FeeRecord;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class DownloadInvoiceController extends Controller
{
    public function __invoke(Request $request, FeeRecord $record, InvoiceService $invoiceService)
    {
        // Ensure user has permission
        if (!auth()->user()->can('classes.manage')) { // Assuming a general admin permission
            abort(403);
        }



        $pdfContent = $invoiceService->generateInvoice($record, false);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-' . $record->id . '.pdf"'
        ]);
    }
}
