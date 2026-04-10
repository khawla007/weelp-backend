<?php

namespace App\Mail;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ItinerarySubmittedCreatorMail extends Mailable
{
    use Queueable, SerializesModels;

    public $itinerary;
    public $creator;

    public function __construct(Itinerary $itinerary, User $creator)
    {
        $this->itinerary = $itinerary;
        $this->creator = $creator;
    }

    public function build()
    {
        return $this->subject('Your Itinerary Has Been Submitted for Review')
                    ->view('emails.itinerary-submitted-creator', [
                        'itinerary' => $this->itinerary,
                        'creator' => $this->creator,
                    ]);
    }
}
