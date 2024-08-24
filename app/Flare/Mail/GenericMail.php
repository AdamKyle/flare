<?php

namespace App\Flare\Mail;

use App\Flare\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $genericMessage;

    /**
     * @var string
     */
    public $genericSubject;

    /**
     * @var bool
     */
    public $dontShowLogin = false;

    /**
     * Create a new message instance.
     *
     * @param  bool  $dontShowLogin  | false
     */
    public function __construct(User $user, string $genericMessage, string $genericSubject, bool $dontShowLogin = false)
    {
        $this->user = $user;
        $this->genericMessage = $genericMessage;
        $this->genericSubject = $genericSubject;
        $this->dontShowLogin = $dontShowLogin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.username'), 'Planes of Tlessa')
            ->subject($this->genericSubject)
            ->view('flare.email.generic-email');
    }
}
