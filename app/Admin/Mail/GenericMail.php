<?php

namespace App\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Flare\Models\User;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $genericMessage;
    public $genericSubject;
    public $dontShowLogin = false;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $genericMessage, $genericSubject, $dontShowLogin = false)
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
