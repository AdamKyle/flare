<?php

namespace App\Game\Maps\Adventure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Character;

class AdventureCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public $adventureLog;
    public $character;

    /**
     * Create a new message instance.
     *
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
                    ->view('game.core.adventures.mail.completed')
                    ->text('game.core.adventures.mail.completed_text');
    }
}
