<?php

namespace App\Game\Shop\Events;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use Illuminate\Queue\SerializesModels;

class SellItemEvent
{
    use SerializesModels;

    /**
     * @var InventorySlot
     */
    public $inventorySlot;

    /**
     * @var Character
     */
    public $character;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(InventorySlot $inventorySlot, Character $character)
    {
        $this->inventorySlot = $inventorySlot;
        $this->character = $character;
    }
}
