<?php

namespace App\Admin\Mail;

use App\Flare\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GeneratedAdmin extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     *
     * @codeCoverageIgnore
     */
    public function build()
    {
        return $this->from(config('mail.username'), 'Planes of Tlessa')
            ->subject('Game Administrator Account Created')
            ->view('admin.email.admin-generated-email', [
                'user' => $this->user,
                'token' => $this->token,
            ]);
    }
}
