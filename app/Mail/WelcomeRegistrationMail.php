<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $dashboardUrl;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->dashboardUrl = config('app.frontend_url') . '/dashboard/customer';
    }

    public function build()
    {
        return $this->subject('Welcome to Weelp!')
                    ->view('emails.welcome-registration', [
                        'name' => $this->name,
                        'dashboardUrl' => $this->dashboardUrl,
                    ]);
    }
}
