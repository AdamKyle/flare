<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class UpdateCharacterAttackEvent
{
    use SerializesModels;

    /**
     * The character.
     *
     * @var \App\Flare\Models\Character;
     */
    public $character;

    /**
     * Create a new event instance.
     *
     * @param  \App\Flare\Models\User $user
     * @return void
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
