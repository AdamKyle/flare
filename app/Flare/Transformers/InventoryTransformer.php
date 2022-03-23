<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use Illuminate\Support\Facades\Cache;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;

class InventoryTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the inventory sheet
     *
     * @param InventorySlot|SetSlot $slot
     * @return mixed
     */
    public function transform(InventorySlot|SetSlot $slot) {
        return [
            'id' => $slot->id,
            'item_name' => $slot->item->affix_name,
            'type' => $slot->item->type,
        ];
    }
}
