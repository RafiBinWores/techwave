<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
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

class AdminInvoiceMail extends Mailable implements ShouldQueue
{ 
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public InvoiceTemplate $template;

    public SiteSetting $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice)
    {
         $this->invoice = $invoice->load('items');
        $this->template = InvoiceTemplate::activeTemplate();
        $this->settings = SiteSetting::current();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->template->subject_prefix ?: 'Invoice').': '.$this->invoice->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
           view: 'emails.admin-invoice',
            with: [
                'invoice' => $this->invoice,
                'template' => $this->template,
                'settings' => $this->settings,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
         PdfFonts::register();

        $pdf = Pdf::loadView('pdf.admin-invoice', [
            'invoice' => $this->invoice,
            'template' => $this->template,
            'settings' => $this->settings,
        ])
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'chroot' => base_path(),
            ]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                ($this->invoice->invoice_no ?? 'invoice').'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
