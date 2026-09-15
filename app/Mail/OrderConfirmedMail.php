<?php

namespace App\Mail;

use App\Models\InvoiceTemplate;
use App\Models\Order;
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

class OrderConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;

    public SiteSetting $settings;

    public InvoiceTemplate $template;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->loadMissing([
            'booking',
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
            subject: 'Order confirmed – '.$planName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmed',
            with: [
                'order' => $this->order,
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
        PdfFonts::register();

        $pdf = Pdf::loadView('pdf.order-invoice', [
            'order' => $this->order,
            'template' => $this->template,
            'setting' => $this->settings,
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
                ($this->order->order_no ?? 'invoice').'.pdf',
            )->withMime('application/pdf'),
        ];
    }

    private function planName(): string
    {
        if ($this->order->order_type === 'pricing_plan') {
            return $this->order->pricingPlan?->title
                ?? $this->order->plan_name
                ?? 'your selected plan';
        }

        return $this->order->servicePlan?->name
            ?? $this->order->service?->card_title
            ?? $this->order->plan_name
            ?? 'your selected service';
    }
}
