<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;

class AttackTimeOutEvent
{
    use SerializesModels;

    public Character $character;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
