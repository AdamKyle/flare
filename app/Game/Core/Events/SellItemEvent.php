<?php

namespace App\Game\Core\Events;

use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;

class SellItemEvent
{
    use SerializesModels;

    public $inventorySlot;

    public $character;

    public function __construct(InventorySlot $inventorySlot, Character $character)
    {
        $this->inventorySlot      = $inventorySlot;
        $this->character          = $character;
    }
}
