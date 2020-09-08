<?php

namespace App\Game\Maps\Adventure\Events;

use App\Flare\Models\Adventure;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Item;

class EmbarkOnAdventureEvent
{
    use SerializesModels;

    public $levelsAtATime;

    public $character;

    public $adventure;

    public function __construct(Character $character, Adventure $adventure, $levelsAtATime = 'all')
    {
        $this->levelsAtATime      = $levelsAtATime;
        $this->character          = $character;
        $this->adventure          = $adventure;
    }
}
