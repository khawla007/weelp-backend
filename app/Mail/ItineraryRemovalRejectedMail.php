<?php

namespace App\Mail;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ItineraryRemovalRejectedMail extends Mailable
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
        return $this->subject('Your Itinerary Removal Request Was Declined')
                    ->view('emails.itinerary-removal-rejected', [
                        'itinerary' => $this->itinerary,
                        'creator' => $this->creator,
                    ]);
    }
}
