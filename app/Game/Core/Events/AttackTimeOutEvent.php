<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\Flare\Models\User;

class AttackTimeOutEvent
{
    use SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * Create a new event instance.
     *
     * @param  Character $character
     * @return void
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
