<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\InvoiceTemplate;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public SiteSetting $settings;

    public InvoiceTemplate $template;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking->loadMissing([
            'user',
            'service',
            'servicePlan',
            'pricingPlan',
        ]);

        $this->settings = SiteSetting::current();
        $this->template = InvoiceTemplate::activeTemplate();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $planName = $this->planName();

        return new Envelope(
            subject: 'Booking placed – '.$planName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-placed',
            with: [
                'booking' => $this->booking,
                'settings' => $this->settings,
                'template' => $this->template,
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

    private function planName(): string
    {
        if ($this->booking->booking_type === 'pricing_plan') {
            return $this->booking->pricingPlan?->title
                ?? $this->booking->plan_name
                ?? 'your selected plan';
        }

        return $this->booking->servicePlan?->name
            ?? $this->booking->service?->card_title
            ?? $this->booking->plan_name
            ?? 'your selected service';
    }
}
