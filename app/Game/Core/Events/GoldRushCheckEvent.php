<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;

class GoldRushCheckEvent {

    use SerializesModels;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * @var Monster $monster
     */
    public Monster $monster;


    /**
     * Create a new event instance.
     *
     * @param Character $character
     * @param Monster $monster
     * @return void
     */
    public function __construct(Character $character, Monster $monster) {
        $this->character = $character;
        $this->monster   = $monster;
    }
}
