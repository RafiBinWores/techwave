<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\SiteSetting;
use App\Models\ToolSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ToolSubscriptionConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ToolSubscription $subscription;

    public SiteSetting $settings;

    public InvoiceTemplate $template;

    public ?Invoice $invoice;

    /**
     * Create a new message instance.
     */
    public function __construct(ToolSubscription $subscription, ?Invoice $invoice = null)
    {
        $this->subscription = $subscription->loadMissing([
            'user',
            'toolCategory',
            'toolPlan',
        ]);

        $this->invoice = $invoice?->load('items');
        $this->settings = SiteSetting::current();
        $this->template = InvoiceTemplate::activeTemplate();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $toolName = $this->subscription->toolCategory?->name ?? 'your selected tool';

        return new Envelope(
            subject: 'Subscription confirmed – '.$toolName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tool-subscription-confirmed',
            with: [
                'subscription' => $this->subscription,
                'settings' => $this->settings,
                'template' => $this->template,
                'invoice' => $this->invoice,
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
        return [];
    }
}
