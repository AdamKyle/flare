<?php

namespace App\Admin\Services;

use App\Flare\Models\SetSlot;
use App\Game\Core\Traits\ResponseBuilder;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;

class ItemsService {

    use ResponseBuilder;

    public function deleteItem(Item $item) {
        $name = $item->name;

        InventorySlot::where('item_id', $item->id)->delete();

        SetSlot::where('item_id', $item->id)->delete();

        foreach ($item->children as $child) {
            InventorySlot::where('item_id', $child->id)->delete();

            SetSlot::where('item_id', $child->id)->get();

            $child->delete();
        }

        $item->delete();

        return $this->successResult(['message' => 'success', $name . ' was deleted successfully.']);
    }
}
