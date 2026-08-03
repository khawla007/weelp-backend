<?php

namespace App\Mail;

use App\Models\SupportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportRequestReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $topicLabel;

    public function __construct(public SupportRequest $supportRequest)
    {
        $this->topicLabel = $this->labelFor($supportRequest->topic);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address((string) config('mail.support_address')),
            ],
            subject: "We received your support request {$this->supportRequest->reference}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-request-receipt',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function labelFor(string $topic): string
    {
        return match ($topic) {
            'dates_availability' => 'Dates & availability',
            'pickup_location' => 'Pickup & location',
            'changes_cancellation' => 'Changes & cancellation',
            'before_booking' => 'Before you book',
            'other' => 'Something else',
            default => $topic,
        };
    }
}
