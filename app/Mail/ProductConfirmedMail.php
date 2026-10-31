<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\ProductPurchase;
use App\Models\SiteSetting;
use App\Support\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use LogicException;

class ProductConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public SiteSetting $settings;

    public InvoiceTemplate $template;

    public function __construct(public ProductPurchase $purchase)
    {
        // Use the settled invoice and saved product names, even if the catalog changes later.
        $purchase->loadMissing(['invoice.items', 'user']);
        $this->invoice = $purchase->invoice ?? throw new LogicException('A confirmed product purchase must have an invoice.');
        $this->settings = SiteSetting::current();
        $this->template = InvoiceTemplate::activeTemplate();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->purchase->renewal_of_id ? 'Subscription renewed – ' : ($this->purchase->billing_cycle === 'one_time' ? 'Purchase confirmed – ' : 'Subscription confirmed – ')).$this->purchase->product_name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.product-confirmed', with: [
            'purchase' => $this->purchase,
            'invoice' => $this->invoice,
            'settings' => $this->settings,
            'template' => $this->template,
        ]);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        PdfFonts::register();

        return [Attachment::fromData(function (): string {
            return Pdf::loadView('pdf.admin-invoice', [
                'invoice' => $this->invoice,
                'template' => $this->template,
                'settings' => $this->settings,
            ])->setPaper('a4')->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'chroot' => base_path(),
            ])->output();
        }, $this->invoice->invoice_no.'.pdf')->withMime('application/pdf')];
    }
}
