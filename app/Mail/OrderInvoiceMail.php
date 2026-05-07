<?php

namespace App\Mail;

use App\Models\InvoiceTemplate;
use App\Models\PricingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public PricingOrder $order;

    public InvoiceTemplate $template;

    public function __construct(PricingOrder $order)
    {
        $this->order = $order->loadMissing([
            'user',
            'pricingPlan',
        ]);

        $this->template = InvoiceTemplate::activeTemplate();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template->subject_prefix.' #'.$this->order->order_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-invoice',
            with: [
                'order' => $this->order,
                'template' => $this->template,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
