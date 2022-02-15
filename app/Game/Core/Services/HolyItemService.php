<?php

namespace App\Game\Core\Services;



use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Facades\DB;

class HolyItemService {

    use ResponseBuilder;

    public function fetchSmithingItems(Character $character): array {
        $slots = $this->getSlots($character);

        return $this->successResult([
            'items'   => $this->fetchValidItems($slots)->values(),
            'alchemy' => $this->fetchAlchemyItems($slots)->values(),
        ]);
    }

    protected function fetchAlchemyItems(DBCollection $slots): Collection {
        return $slots->filter(function($slot) {
            return $slot->item->can_use_on_other_items;
        });
    }

    protected function fetchValidItems(DBCollection $slots): Collection {
        return $slots->filter(function($slot) {
            return $slot->item->holy_stacks > 0;
        });
    }

    protected function getSlots(Character $character): DBCollection {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->where('inventory_slots.equipped', false)->get();
    }
}
