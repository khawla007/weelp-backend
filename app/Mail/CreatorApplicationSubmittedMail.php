<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreatorApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct($application)
    {
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Creator Application Has Been Submitted',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.creator-application-submitted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
