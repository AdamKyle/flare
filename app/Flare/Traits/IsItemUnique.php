<?php

namespace App\Flare\Traits;

use App\Flare\Models\Item;

trait IsItemUnique
{
    public function isUnique(Item $item): bool
    {

        if (! is_null($item->item_suffix_id)) {
            return $item->itemSuffix->randomly_generated;
        }

        if (! is_null($item->item_prefix_id)) {
            return $item->itemPrefix->randomly_generated;
        }

        return false;
    }
}
