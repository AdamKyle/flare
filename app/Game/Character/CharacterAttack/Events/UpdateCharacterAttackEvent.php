<?php

namespace App\Game\Character\CharacterAttack\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterAttackEvent
{
    use SerializesModels;

    public Character $character;

    public bool $ignoreReductions;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character, bool $ignoreReductions = false)
    {
        $this->character = $character;
        $this->ignoreReductions = $ignoreReductions;
    }
}
