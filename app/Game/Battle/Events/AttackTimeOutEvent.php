<?php

namespace App\Game\Battle\Events;

use Illuminate\Queue\SerializesModels;

use App\Flare\Models\Character;

class AttackTimeOutEvent {
    use SerializesModels;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * Create a new event instance.
     *
     * @param  Character $character
     * @return void
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }
}
