<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

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
