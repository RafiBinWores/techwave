<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\ServiceRenewalNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceRenewalNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $kind, public string $actionUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: app(ServiceRenewalNotifications::class)->title($this->kind).' – '.$this->order->order_no);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.service-renewal-notice', with: [
            'heading' => app(ServiceRenewalNotifications::class)->title($this->kind),
            'messageText' => app(ServiceRenewalNotifications::class)->message($this->order, $this->kind),
        ]);
    }
}
