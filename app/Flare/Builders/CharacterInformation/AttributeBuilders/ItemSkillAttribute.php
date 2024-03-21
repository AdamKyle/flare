<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

use App\Flare\Models\Character;
use App\Flare\Builders\Character\Traits\FetchEquipped;

class ItemSkillAttribute {

    use FetchEquipped;

    /**
     * Fetch the item skill attribute based on the progression.
     *
     * @param Character $character
     * @param string $attribute
     * @return float
     */
    public function fetchModifier(Character $character, string $attribute): float {
        $equippedItems = $this->fetchEquipped($character);

        if (is_null($equippedItems)) {
            return 0;
        }

        $slot = $equippedItems->filter(function($slot) {
            return $slot->item->type === 'artifact';
        })->first();

        if (is_null($slot)) {
            return 0;
        }

        $amount = $slot->item->itemSkillProgressions->sum($attribute . '_mod');

        return is_null($amount) ? 0 : $amount;
    }
}
