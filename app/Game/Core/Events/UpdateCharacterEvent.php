<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Adventure;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\Flare\Models\User;

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

    public $dventure;

    /**
     * Create a new event instance.
     *
     * @param  \App\Flare\Models\User $user
     * @return void
     */
    public function __construct(Character $character, Monster $monster, Adventure $adventure = null)
    {
        $this->character = $character;
        $this->monster   = $monster;
        $this->adventure = $adventure;
    }
}
