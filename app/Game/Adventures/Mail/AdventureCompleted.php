<?php

namespace App\Game\Adventures\Mail;

use Illuminate\Bus\Queueable;
use Asahasrabuddhe\LaravelMJML\Mail\Mailable;
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
        return $this->from(config('mail.username'), 'Planes of Tlessa')
                    ->subject('An adventure has been completed!')
                    ->mjml('game.core.adventures.mail.completed', [
                        'adventureLog' => $this->adventureLog,
                        'character'    => $this->character,
                    ]);
    }
}
