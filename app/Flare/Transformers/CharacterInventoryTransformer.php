<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Inventory;

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

                $items['items'][] = $slot->item->load(['itemAffixes', 'artifactProperty']);
            }
        }

        return $items;
    }
}
