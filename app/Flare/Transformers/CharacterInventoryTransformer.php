<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Inventory;
use App\Flare\Models\Item;
use App\Flare\Values\MaxDamageForItemValue;

class CharacterInventoryTransformer extends TransformerAbstract {

    public function transform(Inventory $inventory) {
        $items = ['items' => []];

        if ($inventory->slots->isEmpty()) {
            return $items;
        }

        foreach ($inventory->slots as $slot) {
            if (!is_null($slot->item)) {
                $slot->item->equipped = $slot->equipped;
                $slot->item->actions  = null;
                $slot->item->slot_id  = $slot->id;

                $items['items'][] = $this->transformItem($slot->item->load(['itemAffixes', 'artifactProperty']));
            }
        }

        return $items;
    }

    protected function transformItem(Item $item): Item {
        $item->max_damage = resolve(MaxDamageForItemValue::class)->fetchMaxDamage($item);

        return $item;
    }


}
