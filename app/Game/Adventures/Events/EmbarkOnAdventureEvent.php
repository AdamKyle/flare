<?php

namespace App\Game\Adventures\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;

class EmbarkOnAdventureEvent
{
    use SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * @var Adventure $adventure
     */
    public $adventure;

    /**
     * @var string $attackType
     */
    public $attackType;

    /**
     * Constructor
     *
     * @param Character $character
     * @param Adventure $adventure
     * @param string $attackType
     */
    public function __construct(Character $character, Adventure $adventure, string $attackType)
    {
        $this->character          = $character;
        $this->adventure          = $adventure;
        $this->attackType         = $attackType;
    }
}
