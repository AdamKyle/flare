<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Character\Concerns\FetchEquipped;
use Illuminate\Support\Collection;

class ItemSkillAttribute
{
    use FetchEquipped;

    /**
     * Fetch the item skill attribute based on the progression.
     */
    public function fetchModifier(Character $character, string $attribute): float
    {
        $equippedItems = $this->fetchEquipped($character);

        if (is_null($equippedItems)) {
            return 0;
        }

        $slot = $equippedItems->filter(function ($slot) {
            return $slot->item->type === 'artifact';
        })->first();

        if (is_null($slot)) {
            return 0;
        }

        $amount = $slot->item->itemSkillProgressions->sum($attribute.'_mod');

        return is_null($amount) ? 0 : $amount;
    }

    /**
     * Fetch the equipped artifact item.
     */
    public function fetchArtifactItemEquipped(Character $character): ?Item
    {
        $equippedItems = $this->fetchEquipped($character);

        if (is_null($equippedItems)) {
            return null;
        }

        $slot = $equippedItems->filter(function ($slot) {
            return $slot->item->type === 'artifact';
        })->first();

        return is_null($slot) ? null : $slot->item;
    }

    /**
     * Fetch item skills that effect this stat who are above 0.
     */
    public function fetchItemSkillsThatEffectStat(Item $item, string $stat): Collection
    {
        return $item->itemSkillProgressions->where($stat.'_mod', '>', 0);
    }
}
