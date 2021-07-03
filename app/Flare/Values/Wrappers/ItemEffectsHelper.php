<?php

namespace App\Flare\Values\Wrappers;

use App\Flare\Values\ItemEffectsValue;

class ItemEffectsHelper {

    /**
     * Wrapper around the the KingdomLogStatusValue
     *
     * @param string $effect
     * @return ItemEffectsValue
     * @throws \Exception
     */
    public static function effects(string $effect): ItemEffectsValue {
        return new ItemEffectsValue($effect);
    }
}
