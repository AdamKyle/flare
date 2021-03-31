<?php

namespace App\Game\Adventures\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Character;

class AdventureCompleted extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var AdventureLog $adventureLog
     */
    public $adventureLog;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * Create a new message instance.
     *
     * @param AdventureLog $adventureLog
     * @param Character $character
     * @return void
     */
    public function __construct(AdventureLog $adventureLog, Character $character)
    {
        $this->adventureLog = $adventureLog;
        $this->character    = $character;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('An adventure has been completed!')
                    ->view('game.core.adventures.mail.completed');
    }
}
