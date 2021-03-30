<?php

namespace App\Game\Kingdoms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class KingdomsUpdated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var array $kingdomData
     */
    public $kingdomData;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param array $kingdomData
     * @return void
     */
    public function __construct(User $user, array $kingdomData)
    {
        $this->user        = $user;
        $this->kingdomData = $kingdomData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your kingdoms were updated!')
                    ->view('game.core.kingdoms.mail.updated')
                    ->text('game.core.kingdoms.mail.updated_text');
    }
}
