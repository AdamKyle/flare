<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class CharacterLevelUpEvent
{
    use SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * Constructor
     *
     * @param Character $character
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
