<?php

namespace App\Mail;

use App\Models\InvoiceTemplate;
use App\Models\Proposal;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Proposal $proposal;
    public InvoiceTemplate $template;
    public SiteSetting $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(Proposal $proposal)
    {
        $this->proposal = $proposal->load('items');
        $this->template = InvoiceTemplate::activeTemplate();
        $this->settings = SiteSetting::current();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
             subject: ($this->template->subject_prefix ?: 'Invoice') . ': ' . $this->proposal->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.proposal-invoice',
            with: [
                'proposal' => $this->proposal,
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
        return [];
    }
}
