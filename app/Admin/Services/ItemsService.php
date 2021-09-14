<?php

namespace App\Admin\Services;

use App\Game\Core\Traits\ResponseBuilder;
use Facades\App\Flare\Calculators\SellItemCalculator;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;

class ItemsService {

    use ResponseBuilder;

    public function deleteItem(Item $item) {
        $slots = InventorySlot::where('item_id', $item->id)->get();
        $name  = $item->affix_name;

        if ($slots->isEmpty()) {
            $item->delete();

            return $this->successResult(['message' => 'success', $name . ' was deleted successfully.']);
        }

        foreach($slots as $slot) {
            $character = $slot->inventory->character;

            $slot->delete();

            $gold = SellItemCalculator::fetchTotalSalePrice($item);

            $character->gold += $gold;
            $character->save();

            $character = $character->refresh();

            event(new ServerMessageEvent($character->user, 'deleted_item', $name));
            event(new UpdateTopBarEvent($character));
        }

        $item->delete();

        return $this->successResult(['message' => 'success', $name . ' was deleted successfully.']);
    }
}
