<?php

namespace App\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Flare\Models\User;

class UnBanRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('UnBan Request from: ' . $this->user->character->name)
                    ->view('flare.email.unban_request')
                    ->text('flare.email.unban_request_text');
    }
}
