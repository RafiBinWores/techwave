<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\SiteSetting;
use App\Support\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SubscriptionInvoiceController extends Controller
{
    public function download(Invoice $invoice)
    {
        return response()->streamDownload(
            function () use ($invoice) {
                echo $this->renderPdf($invoice)->output();
            },
            ($invoice->invoice_no ?? 'invoice').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function renderPdf(Invoice $invoice)
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        $invoice->loadMissing('items');

        $template = InvoiceTemplate::activeTemplate();
        $settings = SiteSetting::current();

        PdfFonts::register();

        return Pdf::loadView('pdf.admin-invoice', [
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
    }
}
