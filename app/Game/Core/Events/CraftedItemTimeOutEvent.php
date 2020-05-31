<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;

class CraftedItemTimeOutEvent
{
    use SerializesModels;

    public $character;

    public function __construct(Character $character)
    {
        $this->character = $character;
    }
}
