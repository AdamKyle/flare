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
                $items['items'][] = $slot->item;
            }
        }

        return $items;
    }
}
