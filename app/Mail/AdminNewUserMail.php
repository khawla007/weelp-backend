<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNewUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $username;

    public function __construct(User $user, string $username)
    {
        $this->user = $user;
        $this->username = $username;
    }

    public function build()
    {
        return $this->subject('New User Registered - ' . $this->user->name)
                    ->view('emails.admin-new-user', [
                        'user' => $this->user,
                        'username' => $this->username,
                    ]);
    }
}
