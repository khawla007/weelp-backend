<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CreatorApplicationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct($application)
    {
        $this->application = $application;
    }

    public function build()
    {
        return $this->subject('Your Creator Application Has Been Approved!')
                    ->view('emails.creator-application-approved', [
                        'application' => $this->application,
                    ]);
    }
}
