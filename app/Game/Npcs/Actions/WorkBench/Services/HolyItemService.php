<?php

namespace App\Game\Npcs\Actions\WorkBench\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Services\HolyItemBonusGenerator;
use App\Game\Core\Items\Values\HolyItemLevel;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection;

class HolyItemService
{
    use ResponseBuilder;

    public function __construct(private readonly HolyItemBonusGenerator $holyItemBonusGenerator) {}

    public function fetchSmithingItems(Character $character): array
    {
        $slots = $this->getSlots($character);

        $items = $this->fetchValidItems($slots)->reverse()->values();
        $alchemyItems = $this->fetchAlchemyItems($character)->values();

        return $this->successResult([
            'items' => $items,
            'alchemy_items' => $alchemyItems,
            'costs' => $this->buildCostLookup($items, $alchemyItems),
        ]);
    }

    /**
     * Build the Holy Oil cost lookup, keyed by target inventory slot id and alchemy bag slot id.
     */
    protected function buildCostLookup(Collection $items, Collection $alchemyItems): array
    {
        $costs = [];

        foreach ($items as $itemSlot) {
            foreach ($alchemyItems as $alchemySlot) {
                $costs[$itemSlot->id][$alchemySlot->id] = $this->getCost($itemSlot->item, $alchemySlot->item);
            }
        }

        return $costs;
    }

    public function applyOil(Character $character, array $params): array
    {
        $inventory = Inventory::where('character_id', $character->id)->first();
        $itemSlot = isset($params['inventory_slot_id'])
            ? InventorySlot::where('inventory_id', $inventory->id)->where('id', $params['inventory_slot_id'])->first()
            : InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $params['item_id'])->first();
        $alchemySlotQuery = AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag?->id)
            ->where('character_id', $character->id)
            ->with('item');

        if (isset($params['alchemy_slot_id'])) {
            $alchemySlotQuery->where('id', $params['alchemy_slot_id']);
        } else {
            $alchemySlotQuery->where('item_id', $params['alchemy_item_id'] ?? null);
        }

        $alchemySlot = $alchemySlotQuery->first();

        if (is_null($itemSlot)) {
            return $this->errorResult('No item found to apply the oil to.');
        }

        if ($itemSlot->item->type === 'trinket' || $itemSlot->item->type === 'artifact') {
            return $this->errorResult('Trinkets and Artifacts cannot have holy oils applied.');
        }

        if (is_null($alchemySlot) || ! $alchemySlot->item->can_use_on_other_items || is_null($alchemySlot->item->holy_level)) {
            return $this->errorResult(
                'No alchemy items to apply. Craft some.'
            );
        }

        $cost = $this->getCost($itemSlot->item, $alchemySlot->item);

        if ($cost > $character->gold_dust) {

            return $this->errorResult('Not enough gold dust to apply this oil.');
        }

        if (! $this->canApplyAdditionalStacks($itemSlot->item)) {
            return $this->errorResult('Error: No stacks left.');
        }

        event(new CraftedItemTimeOutEvent($character));

        $character->update([
            'gold_dust' => $character->gold_dust - $cost,
        ]);

        $slot = $this->applyStack($itemSlot, $alchemySlot);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'Applied Holy Oil to: '.$slot->item->affix_name, $slot->id));

        event(new UpdateCharacterCurrenciesEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        if (($slot->item->holy_stacks - $slot->item->holy_stacks_applied) === 0) {
            event(new ServerMessageEvent($character->user, 'You have applied the max stacks allowed. Item has been removed from list of items you can use at the work bench.'));
        }

        return $this->fetchSmithingItems($character);
    }

    public function getCost(Item $item, Item $alchemyItem): int
    {
        $baseCost = $item->holy_stacks * 100;
        $totalCost = $baseCost * $alchemyItem->holy_level;

        return $totalCost;
    }

    protected function canApplyAdditionalStacks(Item $item): bool
    {
        $stacksLeft = $item->holy_stacks - $item->holy_stacks_applied;

        return $stacksLeft > 0;
    }

    protected function applyStack(InventorySlot $itemSlot, AlchemyBagSlot $alchemyItemSlot): InventorySlot
    {
        $holyItemLevel = HolyItemLevel::from($alchemyItemSlot->item->holy_level);

        if ($itemSlot->item->appliedHolyStacks->isEmpty()) {
            $newItem = $itemSlot->item->duplicate();

            $newItem->update([
                'market_sellable' => true,
                'is_mythic' => $itemSlot->item->is_mythic,
                'is_cosmic' => $itemSlot->item->is_cosmic,
            ]);

            $newItem->appliedHolyStacks()->create([
                'item_id' => $newItem->id,
                'devouring_darkness_bonus' => $this->holyItemBonusGenerator->getRandomDevoidanceIncrease($holyItemLevel),
                'stat_increase_bonus' => $this->holyItemBonusGenerator->getRandomStatIncrease($holyItemLevel) / 100,
            ]);

            $inventory = Inventory::find($itemSlot->inventory_id);

            $itemSlot->delete();

            $this->decrementAlchemySlot($alchemyItemSlot);

            return $inventory->slots()->create([
                'inventory_id' => $inventory->id,
                'item_id' => $newItem->id,
            ]);
        }

        $this->decrementAlchemySlot($alchemyItemSlot);

        $itemSlot->item->appliedHolyStacks()->create([
            'item_id' => $itemSlot->item->id,
            'devouring_darkness_bonus' => $this->holyItemBonusGenerator->getRandomDevoidanceIncrease($holyItemLevel),
            'stat_increase_bonus' => $this->holyItemBonusGenerator->getRandomStatIncrease($holyItemLevel) / 100,
        ]);

        return $itemSlot->refresh();
    }

    protected function fetchAlchemyItems(Character $character): Collection
    {
        if (is_null($character->alchemyBag)) {
            return collect();
        }

        return $character->alchemyBag->slots()
            ->where('character_id', $character->id)
            ->whereHas('item', function ($query) {
                $query->where('can_use_on_other_items', true)
                    ->whereNotNull('holy_level');
            })
            ->with('item')
            ->get();
    }

    protected function fetchValidItems(DBCollection $slots): Collection
    {
        return $slots->filter(function ($slot) {
            return ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0;
        });
    }

    protected function getSlots(Character $character): DBCollection
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->where('inventory_slots.equipped', false)->get();
    }

    private function decrementAlchemySlot(AlchemyBagSlot $alchemySlot): void
    {
        if ($alchemySlot->amount <= 1) {
            $alchemySlot->delete();

            return;
        }

        $alchemySlot->update(['amount' => $alchemySlot->amount - 1]);
    }
}
