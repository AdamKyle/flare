<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Adventure;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\User;

class GoldRushCheckEvent
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

    public $adventure;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(Character $character, Monster $monster, Adventure $adventure = null)
    {
        $this->character = $character;
        $this->monster   = $monster;
        $this->adventure = $adventure;
    }
}
