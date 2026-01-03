<?php

namespace App\Game\Character\CharacterSheet\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterBaseDetailsEvent
{
    use SerializesModels;

    public Character $character;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
