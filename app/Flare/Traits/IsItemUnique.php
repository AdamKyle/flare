<?php

namespace App\Flare\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Values\CharacterClassValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait IsItemUnique {

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
