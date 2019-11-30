<?php

namespace App\Game\Maps\Adventure\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\Character;

class MoveTimeOutEvent
{
    use SerializesModels;

    /**
     * The character.
     *
     * @var \App\Flare\Models\Monster;
     */
    public $character;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
