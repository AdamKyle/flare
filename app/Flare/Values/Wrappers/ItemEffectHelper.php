<?php

namespace App\Flare\Values\Wrappers;

use App\Flare\Values\ItemEffectsValue;

class ItemEffectHelper {

    /**
     * Wrapper around the the KingdomLogStatusValue
     *
     * @param string $effect
     * @return ItemEffectsValue
     * @throws \Exception
     */
    public static function statusType(string $effect): ItemEffectsValue {
        return new ItemEffectsValue($effect);
    }

    /**
     * Gets the value for walk on watter.
     *
     * @return string
     */
    public function walkOnWaterValue(): string {
        return ItemEffectsValue::WALKONWATER;
    }

    /**
     * Gets the value for labyrinth
     *
     * @return string
     */
    public function labyrinthAccess(): string {
        return ItemEffectsValue::LABYRINTH;
    }
}
