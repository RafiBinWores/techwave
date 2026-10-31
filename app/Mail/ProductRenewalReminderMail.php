<?php

namespace App\Mail;

use App\Models\InvoiceTemplate;
use App\Models\ProductPurchase;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// The reminder job owns queueing and retries; this mailable sends before delivery is recorded.
class ProductRenewalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProductPurchase $purchase, public string $kind, public string $actionUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->kind === 'week' ? '7-day renewal reminder – ' : '24-hour renewal reminder – ').$this->purchase->product_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.product-renewal-reminder', with: [
            'settings' => SiteSetting::current(),
            'template' => InvoiceTemplate::activeTemplate(),
            'windowLabel' => $this->kind === 'week' ? '7-day reminder' : '24-hour reminder',
        ]);
    }
}
