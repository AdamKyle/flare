<?php

namespace App\Game\Shop\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Queue\SerializesModels;

class BuyItemEvent
{
    use SerializesModels;

    /**
     * @var Item
     */
    public $item;

    /**
     * @var Character
     */
    public $character;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(Item $item, Character $character)
    {
        $this->item = $item;
        $this->character = $character;
    }
}
