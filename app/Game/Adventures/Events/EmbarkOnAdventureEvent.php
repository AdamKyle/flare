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
     * Constructor
     * 
     * @param Character $character
     * @param Adventure $adventure
     * @return void
     */
    public function __construct(Character $character, Adventure $adventure)
    {
        $this->character          = $character;
        $this->adventure          = $adventure;
    }
}
