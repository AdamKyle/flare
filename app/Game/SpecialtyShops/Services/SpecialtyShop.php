<?php

namespace App\Game\SpecialtyShops\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;

class SpecialtyShop
{
    use ResponseBuilder;

    /**
     * Purchase the item.
     *
     * @throws Exception
     */
    public function purchaseItem(Character $character, int $itemId, string $type): array
    {
        $item = Item::where('id', $itemId)
            ->where('specialty_type', $type)
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->doesntHave('appliedHolyStacks')
            ->first();

        if (is_null($item)) {
            return $this->errorResult('Item is not found.');
        }

        if (! $this->hasCurrency($character, $item)) {
            return $this->errorResult('You do not have the currencies to purchase this.');
        }

        if (! $this->hasTypeOfItemToTrade($character, $type, $item->type)) {
            $specialtyType = new ItemSpecialtyType($type);

            if ($specialtyType->isPurgatoryChains()) {
                return $this->errorResult('You are missing an item of type: ' . $item->type . ' which must be of specialty type: ' . ItemSpecialtyType::HELL_FORGED . '. Item must be in your inventory.');
            }

            return $this->errorResult('You are missing an item of type: ' . $item->type . ' with a crafting level of 400. Item must be in your inventory.');
        }

        $slotToTrade = $this->getItemToTrade($character, $type, $item->type);
        $itemToTrade = $slotToTrade->item;

        // Only duplicate the item if we have either a prefix, suffix or holy stacks applied.
        // If not, use the item we want to buy and set its id, so we don't duplicate the item.
        if (!is_null($itemToTrade->item_suffix_id) || !is_null($itemToTrade->item_prefix_id) || $itemToTrade->appliedHolyStacks->isNotEmpty()) {
            $newItemToBuy = $this->moveEnchantmentsAndHoly($itemToTrade, $item);
        } else {
            $newItemToBuy = $item;
        }

        $character->inventory->slots()->create([
            'item_id' => $newItemToBuy->id,
            'inventory_id' => $character->inventory->id,
        ]);

        $character = $character->refresh();

        $this->updateCharacterCurrencies($character, $item);

        event(new UpdateCharacterInventoryCountEvent($character));

        $slotToTrade->delete();

        event(new ServerMessageEvent($character->user, 'You bought a new: ' . $item->name . ' (' . $item->type . ') from the ' . $item->specialty_type . ' shop.', $newItemToBuy->id));

        return $this->successResult();
    }

    /**
     * Move enchantments and holy items.
     *
     * - Duplicate the item to buy
     * - Move Enchantments
     * - Move Holy stacks
     */
    protected function moveEnchantmentsAndHoly(Item $itemToTrade, Item $itemToBuy): Item
    {
        $duplicatedItem = $itemToBuy->duplicate();

        $duplicatedItem->update([
            'item_prefix_id' => $itemToTrade->item_prefix_id,
            'item_suffix_id' => $itemToTrade->item_suffix_id,
        ]);

        $duplicatedItem = $duplicatedItem->refresh();

        $duplicatedItem = $this->applyHolyStacks($itemToTrade, $duplicatedItem);

        $hasItemAffix = (! is_null($duplicatedItem->item_prefix_id) || ! is_null($duplicatedItem->item_suffix_id));
        $hasHoly = $duplicatedItem->appliedHolyStacks->count() > 0;

        if ($hasItemAffix || $hasHoly) {
            $duplicatedItem->update([
                'market_sellable' => true,
                'is_mythic' => $itemToTrade->is_mythic,
                'is_cosmic' => $itemToTrade->is_cosmic,
            ]);
        }

        return $duplicatedItem;
    }

    /**
     * Apply holy stacks from the old item to the new one.
     */
    protected function applyHolyStacks(Item $itemToTrade, Item $itemToBuy): Item
    {
        if ($itemToTrade->appliedHolyStacks()->count() > 0) {

            foreach ($itemToTrade->appliedHolyStacks as $stack) {
                $stackAttributes = $stack->getAttributes();

                $stackAttributes['item_id'] = $itemToBuy->id;

                $itemToBuy->appliedHolyStacks()->create($stackAttributes);
            }
        }

        return $itemToBuy->refresh();
    }

    /**
     * Does the character have the currency?
     *
     * @throws Exception
     */
    protected function hasCurrency(Character $character, Item $item): bool
    {

        $goldCost = is_null($item->cost) ? 0 : $item->cost;
        $shardsCost = is_null($item->shards_cost) ? 0 : $item->shards_cost;
        $copperCoinsCost = is_null($item->copper_coin_cost) ? 0 : $item->copper_coin_cost;
        $goldDustCost = is_null($item->gold_dust_cost) ? 0 : $item->gold_dust_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = $goldDustCost - $goldDustCost * 0.05;
            $goldCost = $goldCost - $goldCost * 0.05;
            $shardsCost = $shardsCost - $shardsCost * 0.05;
            $copperCoinsCost = $copperCoinsCost - $copperCoinsCost * 0.05;
        }

        if (
            $character->gold < $goldCost ||
            $character->gold_dust < $goldDustCost ||
            $character->shards < $shardsCost ||
            $character->copper_coins < $copperCoinsCost
        ) {
            return false;
        }

        return true;
    }

    /**
     * Update the character currencies.
     *
     * @throws Exception
     */
    protected function updateCharacterCurrencies(Character $character, Item $itemToBuy): void
    {
        $goldCost = is_null($itemToBuy->cost) ? 0 : $itemToBuy->cost;
        $shardsCost = is_null($itemToBuy->shards_cost) ? 0 : $itemToBuy->shards_cost;
        $copperCoinsCost = is_null($itemToBuy->copper_coin_cost) ? 0 : $itemToBuy->copper_coin_cost;
        $goldDustCost = is_null($itemToBuy->gold_dust_cost) ? 0 : $itemToBuy->gold_dust_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = $goldDustCost - $goldDustCost * 0.05;
            $goldCost = $goldCost - $goldCost * 0.05;
            $shardsCost = $shardsCost - $shardsCost * 0.05;
            $copperCoinsCost = $copperCoinsCost - $copperCoinsCost * 0.05;
        }

        $character->update([
            'gold' => $character->gold - $goldCost,
            'gold_dust' => $character->gold_dust - $goldDustCost,
            'shards' => $character->shards - $shardsCost,
            'copper_coins' => $character->copper_coins - $copperCoinsCost,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));
    }

    /**
     * Does the character have the item type to trade?
     *
     * - Item must be of the same type as the item to buy.
     * - Item must be of level 400.
     * - If the item is of purgatory chains, the type to trade must be of hell forged.
     *
     * @throws Exception
     */
    protected function hasTypeOfItemToTrade(Character $character, string $specialtyType, string $itemType): bool
    {
        return ! is_null($this->getItemToTrade($character, $specialtyType, $itemType));
    }

    /**
     * Get the item to trade.
     *
     * - Item must be of the same type as the item to buy.
     * - Item must be of level 400.
     * - If the item is of purgatory chains, the type to trade must be hell forged.
     * - If the item is of twisted earth, the type to trade must be purgatory chains.
     *
     * @throws Exception
     */
    protected function getItemToTrade(Character $character, string $specialtyType, string $itemType): ?InventorySlot
    {
        $specialtyType = new ItemSpecialtyType($specialtyType);

        if ($specialtyType->isPurgatoryChains()) {
            return $character->inventory->slots->filter(function ($slot) use ($itemType) {
                if ($slot->item->type === $itemType && $slot->item->specialty_type === ItemSpecialtyType::HELL_FORGED) {
                    return $slot;
                }
            })->first();
        }

        if ($specialtyType->isTwistedEarth()) {
            return $character->inventory->slots->filter(function ($slot) use ($itemType) {
                if ($slot->item->type === $itemType && $slot->item->specialty_type === ItemSpecialtyType::PURGATORY_CHAINS) {
                    return $slot;
                }
            })->first();
        }

        return $character->inventory->slots->filter(function ($slot) use ($itemType) {
            if ($slot->item->type === $itemType) {
                if ($slot->item->skill_level_required === 400) {
                    return $slot;
                }
            }
        })->first();
    }
}
