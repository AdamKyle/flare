<?php

namespace App\Flare\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var string $token
     */
    public $token;


    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $token
     * @return void
     */
    public function __construct(User $user, string $token)
    {
        $this->user  = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.username'), 'Planes of Tlessa')
                    ->subject('Password reset')
                    ->view('flare.email.password_reset', [
                        'token' => $this->token,
                    ]);
    }
}
