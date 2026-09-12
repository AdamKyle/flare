<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;

class HolyOilOilPoolResolver
{
    /**
     * Resolve the next available Holy Oil from the character's selected oil slot pool.
     *
     * Preserves the order the character selected the oil slots in, exhausting one slot's
     * amount before the next is ever considered.
     *
     * @param Character $character The character running the batch.
     * @param array<int, int> $oilSlotIds The character-selected Holy Oil Alchemy Bag slot ids.
     * @return AlchemyBagSlot|null The next available Holy Oil slot, or null when the pool is exhausted.
     */
    public function nextAvailableOil(Character $character, array $oilSlotIds): ?AlchemyBagSlot
    {
        if (is_null($character->alchemyBag)) {
            return null;
        }

        $slots = AlchemyBagSlot::with('item')
            ->where('alchemy_bag_id', $character->alchemyBag->id)
            ->where('character_id', $character->id)
            ->whereIn('id', $oilSlotIds)
            ->where('amount', '>', 0)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->where('can_use_on_other_items', true)->whereNotNull('holy_level');
            })
            ->get()
            ->keyBy('id');

        foreach ($oilSlotIds as $oilSlotId) {
            if ($slots->has($oilSlotId)) {
                return $slots->get($oilSlotId);
            }
        }

        return null;
    }
}
