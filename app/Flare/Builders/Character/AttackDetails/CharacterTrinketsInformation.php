<?php

namespace App\Flare\Builders\Character\AttackDetails;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;

class CharacterTrinketsInformation {

    use FetchEquipped;

    /**
     * Get the character ambush chance.
     *
     * @param Character $character
     * @return float
     */
    public function getAmbushChance(Character $character): float {
        $equipped = $this->fetchEquipped($character);

        $ambushChance = 0.0;

        if (is_null($equipped)) {
            return $ambushChance;
        }

        foreach ($equipped as $slot) {
            $ambushChance += $slot->item->ambush_chance;
        }

        return $ambushChance;
    }

    /**
     * Get the character ambush resistance chance.
     *
     * @param Character $character
     * @return float
     */
    public function getAmbushResistanceChance(Character $character): float {
        $equipped = $this->fetchEquipped($character);

        $ambushResistance = 0.0;

        if (is_null($equipped)) {
            return $ambushResistance;
        }

        foreach ($equipped as $slot) {
            $ambushResistance += $slot->item->ambush_resistance;
        }

        return $ambushResistance;
    }

    /**
     * Get Character Counter Chance.
     *
     * @param Character $character
     * @return float
     */
    public function getCounterChance(Character $character): float {
        $equipped = $this->fetchEquipped($character);

        $counterChance = 0.0;

        if (is_null($equipped)) {
            return $counterChance;
        }

        foreach ($equipped as $slot) {
            $counterChance += $slot->item->counter_chance;
        }

        return $counterChance;
    }

    /**
     * Get counter resistance chance.
     *
     * @param Character $character
     * @return float
     */
    public function getCounterResistanceChance(Character $character): float {
        $equipped = $this->fetchEquipped($character);

        $counterResistance = 0.0;

        if (is_null($equipped)) {
            return $counterResistance;
        }

        foreach ($equipped as $slot) {
            $counterResistance += $slot->item->counter_resistance;
        }

        return $counterResistance;
    }
}
