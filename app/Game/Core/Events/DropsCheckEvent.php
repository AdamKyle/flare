<?php

namespace App\Game\Core\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use Illuminate\Queue\SerializesModels;

class DropsCheckEvent
{
    use SerializesModels;

    public Character $character;

    public Monster $monster;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character, Monster $monster)
    {
        $this->character = $character;
        $this->monster = $monster;
    }
}
