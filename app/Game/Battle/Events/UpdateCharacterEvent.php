<?php

namespace App\Game\Battle\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\User;

class UpdateCharacterEvent
{
    use SerializesModels;

    /**
     * The character.
     *
     * @var \App\Flare\Models\Monster;
     */
    public $character;

    /**
     * The monster.
     *
     * @var \App\Flare\Models\Monster;
     */
    public $monster;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(Character $character, Monster $monster)
    {
        $this->character = $character;
        $this->monster   = $monster;
    }
}
