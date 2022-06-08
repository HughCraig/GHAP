<?php

namespace TLCMap\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailChangedOld extends Mailable
{
    use Queueable, SerializesModels;

    public $old_email;
    public $new_email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $old_email, string $new_email)
    {
        $this->old_email = $old_email;
        $this->new_email = $new_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("TLCMap Email Changed")->view('emails.emailchangedold');
    }
}
