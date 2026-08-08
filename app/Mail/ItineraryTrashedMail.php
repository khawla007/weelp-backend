<?php

namespace App\Mail;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ItineraryTrashedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Itinerary $itinerary,
        public User $creator,
        public Carbon $purgeAt,
    ) {}

    public function build(): self
    {
        return $this->subject('Your Itinerary Was Moved to Trash')
            ->view('emails.itinerary-trashed');
    }
}
