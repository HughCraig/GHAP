<?php

namespace TLCMap\Listeners;

use Illuminate\Auth\Events\Registered;
use TLCMap\Notifications\VerifyEmail;


class SendEmailVerification
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event)
    {
        $event->user->notify(new VerifyEmail());
    }
}
