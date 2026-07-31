<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use League\Fractal\TransformerAbstract;

class CharacterInventoryCountTransformer extends TransformerAbstract
{
    public function transform(Character $character): array
    {
        $alchemyBagCount = $character->getAlchemyBagCount();
        $gemBagCount = $character->getGemBagCount();
        $craftedItemsSet = InventorySet::where('character_id', $character->id)
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();
        $craftedItemsSetCount = $craftedItemsSet?->slots()->count() ?? 0;
        $craftedItemsSetMax = $craftedItemsSet?->max_slots ?? InventorySet::BATCH_CRAFTING_MAX_SLOTS;

        return [
            'inventory_max' => $character->inventory_max,
            'inventory_count' => $character->getInventoryCount(),
            'inventory_bag_count' => $character->getInventoryCount(),
            'alchemy_item_count' => $alchemyBagCount,
            'alchemy_bag_count' => $alchemyBagCount,
            'alchemy_bag_limit' => $character->alchemy_bag_limit,
            'is_alchemy_bag_full' => $character->isAlchemyBagFull(),
            'gem_bag_count' => $gemBagCount,
            'gem_bag_limit' => $character->gem_bag_limit,
            'is_gem_bag_full' => $character->isGemBagFull(),
            'crafted_items_set_count' => $craftedItemsSetCount,
            'crafted_items_set_max' => $craftedItemsSetMax,
        ];
    }
}
