<?php

namespace TLCMap\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CollaboratorEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $sharelink;
    public $senderemail;
    public $dsrole;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $sharelink, string $senderemail, string $dsrole)
    {
        $this->sharelink = $sharelink;
        $this->senderemail = $senderemail;
        $this->dsrole = strtolower($dsrole);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.collaboratoremail');
    }
}
