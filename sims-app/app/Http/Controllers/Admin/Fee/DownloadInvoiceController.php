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
        $user = auth()->user();
        if ($user->role !== 'admin' && !$user->hasRole('Super Admin')) {
            abort_if(!$user->can('fees.manage'), 403);
        }



        $pdfContent = $invoiceService->generateInvoice($record, false);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-' . $record->id . '.pdf"'
        ]);
    }
}
