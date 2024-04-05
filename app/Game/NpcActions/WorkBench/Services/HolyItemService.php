<?php

namespace App\Game\NpcActions\WorkBench\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Values\ItemHolyValue;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection;

class HolyItemService {

    use ResponseBuilder;

    public function fetchSmithingItems(Character $character): array {
        $slots = $this->getSlots($character);

        return $this->successResult([
            'items'         => $this->fetchValidItems($slots)->reverse()->values(),
            'alchemy_items' => $this->fetchAlchemyItems($slots)->values(),
        ]);
    }

    public function applyOil(Character $character, array $params): array {
        event(new CraftedItemTimeOutEvent($character));

        $inventory   = Inventory::where('character_id', $character->id)->first();
        $itemSlot    = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $params['item_id'])->first();
        $alchemySlot = InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $params['alchemy_item_id'])->first();

        if ($itemSlot->item->type === 'trinket' || $itemSlot->item->type === 'artifact') {
            return $this->errorResult('Trinkets and Artifacts cannot have holy oils applied.');
        }

        $cost = $this->getCost($itemSlot->item, $alchemySlot->item);

        if ($cost > $character->gold_dust) {

            return $this->errorResult('Not enough gold dust to apply this oil.');
        }

        if (!$this->canApplyAdditionalStacks($itemSlot->item)) {
            return $this->errorResult('Error: No stacks left.');
        }

        $character->update([
            'gold_dust' => $character->gold_dust - $cost,
        ]);

        $slot = $this->applyStack($itemSlot, $alchemySlot);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'Applied Holy Oil to: ' . $slot->item->affix_name, $slot->id));

        event(new UpdateCharacterCurrenciesEvent($character));

        if (($slot->item->holy_stacks - $slot->item->holy_stacks_applied) === 0) {
            event(new ServerMessageEvent($character->user, 'You have applied the max stacks allowed. Item has been removed from list of items you can use at the work bench.'));
        }

        return $this->fetchSmithingItems($character);
    }

    protected function getCost(Item $item, Item $alchemyItem): int {
        $baseCost  = $item->holy_stacks * 100;
        $totalCost = $baseCost * $alchemyItem->holy_level;

        return $totalCost;
    }

    protected function canApplyAdditionalStacks(Item $item): bool {
        $stacksLeft = $item->holy_stacks - $item->holy_stacks_applied;

        return $stacksLeft > 0;
    }

    protected function applyStack(InventorySlot $itemSlot, InventorySlot $alchemyItemSlot): InventorySlot {
        $holyItemEffect = new ItemHolyValue($alchemyItemSlot->item->holy_level);

        if ($itemSlot->item->appliedHolyStacks->isEmpty()) {
            $newItem = $itemSlot->item->duplicate();

            $newItem->update([
                'market_sellable' => true,
                'is_mythic'       => $itemSlot->item->is_mythic,
                'is_cosmic'       => $itemSlot->item->is_cosmic,
            ]);

            $newItem->appliedHolyStacks()->create([
                'item_id'                  => $newItem->id,
                'devouring_darkness_bonus' => $holyItemEffect->getRandomDevoidanceIncrease(),
                'stat_increase_bonus'      => $holyItemEffect->getRandomStatIncrease() / 100,
            ]);

            $inventory = Inventory::find($itemSlot->inventory_id);

            $itemSlot->delete();

            $alchemyItemSlot->delete();

            return $inventory->slots()->create([
                'inventory_id' => $inventory->id,
                'item_id'      => $newItem->id,
            ]);
        }

        $alchemyItemSlot->delete();

        $itemSlot->item->appliedHolyStacks()->create([
            'item_id'                  => $itemSlot->item->id,
            'devouring_darkness_bonus' => $holyItemEffect->getRandomDevoidanceIncrease(),
            'stat_increase_bonus'      => $holyItemEffect->getRandomStatIncrease() / 100,
        ]);

        return $itemSlot->refresh();
    }

    protected function fetchAlchemyItems(DBCollection $slots): Collection {
        return $slots->filter(function($slot) {
            return $slot->item->can_use_on_other_items;
        });
    }

    protected function fetchValidItems(DBCollection $slots): Collection {
        return $slots->filter(function($slot) {
            return ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0;
        });
    }

    protected function getSlots(Character $character): DBCollection {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->where('inventory_slots.equipped', false)->get();
    }
}
