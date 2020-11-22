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
     * @var Character $character
     */
    public $character;

    /**
     * @var Monster $monster
     */
    public $monster;

    /**
     * @var Adventure $adventure
     */
    public $dventure;

    /**
     * Create a new event instance.
     *
     * @param Character $character
     * @param Monster $monster
     * @param Adventure $adventure | null
     * @return void
     */
    public function __construct(Character $character, Monster $monster, Adventure $adventure = null)
    {
        $this->character = $character;
        $this->monster   = $monster;
        $this->adventure = $adventure;
    }
}
