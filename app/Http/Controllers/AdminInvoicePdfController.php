<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\SiteSetting;
use App\Support\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminInvoicePdfController extends Controller
{
    public function download(Invoice $invoice)
    {
        $invoice->loadMissing('items');

        $template = InvoiceTemplate::activeTemplate();
        $settings = SiteSetting::current();

        PdfFonts::register();

        $pdf = Pdf::loadView('pdf.admin-invoice', [
            'invoice' => $invoice,
            'template' => $template,
            'settings' => $settings,
        ])
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'chroot' => base_path(),
            ]);

        return $pdf->download(($invoice->invoice_no ?? 'invoice').'.pdf');
    }
}
