<?php

namespace App\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
     * @param string $genericSubject
     * @param string $genericSubject
     * @param bool $dontShowLogin | false
     * @return void
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
        return $this->subject($this->genericSubject)
                    ->view('admin.email.generic_mail')
                    ->text('admin.email.generic_mail_text');
    }
}
