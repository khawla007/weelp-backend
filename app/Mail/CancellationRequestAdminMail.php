<?php

namespace App\Mail;

use App\Mail\Support\CancellationMailContext;
use App\Models\CancellationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CancellationRequestAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CancellationRequest $cancellationRequest)
    {
        $this->cancellationRequest->loadMissing(['order', 'customer']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Cancellation request #{$this->cancellationRequest->id} needs review");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.cancellation.admin-request',
            with: CancellationMailContext::forAdmin($this->cancellationRequest),
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
