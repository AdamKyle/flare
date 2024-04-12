<?php

namespace App\Game\Character\CharacterAttack\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterAttackEvent
{
    use SerializesModels;

    /**
     * @var Character $character;
     */
    public Character $character;

    /**
     * @var bool $ignoreReductions
     */
    public bool $ignoreReductions;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     * @param bool $ignoreReductions
     */
    public function __construct(Character $character, bool $ignoreReductions = false) {
        $this->character        = $character;
        $this->ignoreReductions = $ignoreReductions;
    }
}
