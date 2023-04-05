<?php

namespace App\Game\Core\Gems\Services;

use Illuminate\Support\Collection;
use App\Flare\Models\Item;

class ItemAtonements {

    private GemComparison $gemComparison;

    public function __construct(GemComparison $gemComparison) {
        $this->gemComparison = $gemComparison;
    }

    public function getAtonements(Item $item, Collection $inventorySlots): array {

        $itemsAtonement = $this->gemComparison->getElementAtonement($item);
        $inventoryAtonements = [];

        foreach ($inventorySlots as $slot) {
            $inventoryAtonements[] = [
                'data'      => $this->gemComparison->getElementAtonement($slot->item),
                'item_name' => $slot->item->affix_name,
            ];
        }

        return [
            'item_atonement'       => $itemsAtonement,
            'inventory_atonements' => $inventoryAtonements,
        ];
    }
}
