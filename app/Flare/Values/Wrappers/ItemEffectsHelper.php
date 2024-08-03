<?php

namespace App\Flare\Values\Wrappers;

use App\Flare\Values\ItemEffectsValue;

class ItemEffectsHelper
{
    /**
     * Wrapper around the KingdomLogStatusValue
     *
     * @throws \Exception
     */
    public static function effects(string $effect): ItemEffectsValue
    {
        return new ItemEffectsValue($effect);
    }
}
