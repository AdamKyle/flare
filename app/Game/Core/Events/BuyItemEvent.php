<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Item;

class BuyItemEvent
{
    use SerializesModels;

    public $item;

    public $character;

    public function __construct(Item $item, Character $character)
    {
        $this->item      = $item;
        $this->character = $character;
    }
}
