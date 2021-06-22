<?php

namespace App\Admin\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use Illuminate\Database\Eloquent\Collection;

class ItemAffixService {

    /**
     * Delete the Affix.
     *
     * We also remove the affix from any addition items it might be attached to.
     *
     * Once done, we delete the affix.
     *
     * @param ItemAffix $affix
     * @return void
     */
    public function deleteAffix(ItemAffix $affix): void {
        $column             = 'item_'.$affix->type.'_id';
        $name               = $affix->name;
        $itemsWithThisAffix = Item::where($column, $affix->id)->get();

        if ($itemsWithThisAffix->isNotEmpty()) {
            $this->handleItemsWithAffix($itemsWithThisAffix, $affix, $column, $name);
        }

        $affix->delete();
    }

    protected function handleItemsWithAffix(Collection $items, ItemAffix $affix, string $column, string $name) {
        foreach($items as $item) {
            $slots = InventorySlot::where('item_id', $item->id)->get();

            $item->{$column} = null;
            $item->save();

            if (is_null($item->itemPrefix) && is_null($item->itemSuffix)) {
                $this->swapItemForCharacter($item, $slots);
            }

            if ($slots->isNotEmpty()) {
                $this->handleSlots($slots, $affix, $name);
            }

            $this->deleteFromMarketBoard($item);
        }
    }

    protected function swapItemForCharacter(Item $item, Collection $slots) {
        $total = Item::where('name', $item->name)->count();

        if ($total > 1 && $slots->isNotEmpty()) {
            foreach ($slots as $slot) {
                // Swap for an item with no prefix or suffix for the character:
                $slot->update([
                    'item_id' => Item::where('name', $item->name)
                                        ->where('id', '!=', $item->id)
                                        ->where('item_suffix_id', null)
                                        ->where('item_prefix_id', null)
                                        ->first()->id,
                ]);
            }
        } else if ($total > 1) {
            $item->delete();
        }
    }

    protected function deleteFromMarketBoard(Item $item) {
        foreach (MarketHistory::where('item_id', $item->id)->get() as $history) {
            $history->delete();
        }

        foreach (MarketBoard::where('item_id', $item->id)->get() as $board) {
            $board->delete();
        }
    }

    protected function handleSlots(Collection $slots, ItemAffix $affix, string $name) {
        foreach ($slots as $slot) {

            $character = $slot->inventory->character;

            $character->gold += $affix->cost;
            $character->save();

            $character = $character->refresh();

            $forMessages = $name . ' has been removed from one or more of your items. You have been compensated the amount of: ' . $affix->cost;

            event(new ServerMessageEvent($character->user, 'deleted_affix', $forMessages));
            event(new UpdateTopBarEvent($character));
        }
    }
}
