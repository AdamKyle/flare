<?php

namespace App\Flare\Mail;

use Illuminate\Bus\Queueable;
use Asahasrabuddhe\LaravelMJML\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var string $genericMessage
     */
    public $genericMessage;

    /**
     * @var string $genericSubject
     */
    public $genericSubject;

    /**
     * @var bool $dontShowLogin
     */
    public $dontShowLogin = false;


    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $genericMessage
     * @param string $genericSubject
     * @param bool $dontShowLogin | false
     */
    public function __construct(User $user, string $genericMessage, string $genericSubject, bool $dontShowLogin = false)
    {
        $this->user             = $user;
        $this->genericMessage   = $genericMessage;
        $this->genericSubject   = $genericSubject;
        $this->dontShowLogin    = $dontShowLogin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->from(config('mail.username'), 'Planes of Tlessa')
                    ->subject($this->genericSubject)
                    ->mjml('flare.email.generic_mail', [
                        'user'           => $this->user,
                        'genericMessage' => $this->genericMessage,
                        'genericSubject' => $this->genericSubject,
                        'dontShowLogin'  => $this->dontShowLogin
                    ]);
    }
}
