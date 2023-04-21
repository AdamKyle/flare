<?php

namespace App\Game\Shop\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;

class SellItemEvent
{
    use SerializesModels;

    /**
     * @var InventorySlot $inventorySlot
     */
    public $inventorySlot;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * Constructor
     *
     * @param InventorySlot $inventorySlot
     * @param Character $character
     * @return void
     */
    public function __construct(InventorySlot $inventorySlot, Character $character)
    {
        $this->inventorySlot      = $inventorySlot;
        $this->character          = $character;
    }
}
