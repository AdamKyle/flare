<?php

namespace App\Game\Shop\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Item;

class BuyItemEvent
{
    use SerializesModels;

    /**
     * @var Item $item
     */
    public $item;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * Constructor
     *
     * @param Item $item
     * @param Character $character
     * @return void
     */
    public function __construct(Item $item, Character $character)
    {
        $this->item      = $item;
        $this->character = $character;
    }
}
