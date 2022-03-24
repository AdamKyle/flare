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
            'id'                      => $slot->id,
            'item_name'               => $slot->item->affix_name,
            'type'                    => $slot->item->type,
            'description'             => $slot->item->description,
            'attached_affixes_count'  => $this->getAffixCount($slot->item),
            'is_unique'               => $this->isUnique($slot->item),
            'has_holy_stacks_applied' => $slot->item->holy_stacks_applied,
            'ac'                      => $slot->item->getTotalDefence(),
            'attack'                  => $slot->item->getTotalDamage(),
        ];
    }

    protected function getAffixCount(Item $item): int {
        if (!is_null($item->item_prefix_id) && !is_null($item->item_suffix_id)) {
            return 2;
        }

        if (is_null($item->item_prefix_id) && !is_null($item->item_suffix_id)) {
            return 1;
        }

        if (!is_null($item->item_prefix_id) && is_null($item->item_suffix_id)) {
            return 1;
        }

        return 0;
    }

    public function isUnique(Item $item): bool {

        if (!is_null($item->item_suffix_id)) {
            return $item->itemSuffix->randomly_generated;
        }

        if (!is_null($item->item_prefix_id)) {
            return $item->itemPrefix->randomly_generated;
        }

        return false;
    }
}
