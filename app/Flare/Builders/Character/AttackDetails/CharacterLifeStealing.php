<?php

namespace App\Flare\Builders\Character\AttackDetails;

use Illuminate\Support\Collection;

class CharacterLifeStealing {

    /**
     * Gets the total life stealing % from the affixes.
     *
     * @param Collection $slots
     * @param string $type
     * @return float
     */
    public function handleLifeStealingAmount(Collection $slots, string $type): float {
        $values       = $this->fetchAmountOfLifeStealing($slots, $type);
        $totalPercent = $this->calculateLifeStealingPercentage($values);

        if ($totalPercent > 1.0) {
            return 0.99;
        }

        if (is_null($totalPercent)) {
            return 0.0;
        }

        return $totalPercent;
    }

    /**
     * Fetch Life Stealing Amount
     *
     * @param Collection $slots
     * @param string $affixType
     * @return array
     */
    public function fetchAmountOfLifeStealing(Collection $slots, string $affixType): array {
        $values = [];

        foreach ($slots as $slot) {
            if (!is_null($slot->item->{$affixType})) {
                if (empty($values)) {
                    $values[] = $slot->item->{$affixType}->steal_life_amount;
                } else {
                    $values[] = ($slot->item->{$affixType}->steal_life_amount);
                }
            }
        }

        return $values;
    }

    /**
     * Calculates the total life stealing percentage.
     *
     * @param array $values
     * @return float
     */
    public function calculateLifeStealingPercentage(array $values): float {
        rsort($values);

        $totalPercent     = 0;
        $additionalValues = [];

        foreach ($values as $value) {
            if ($totalPercent === 0) {
                $totalPercent = $value;
            } else {
                $additionalValues[] = ($value / 2);
            }
        }

        $sumOfValues = array_sum($additionalValues);

        if ($sumOfValues > 0) {
            $totalPercent = $totalPercent * ($sumOfValues * 0.75);
        }

        if (is_null($totalPercent)) {
            return 0.0;
        }

        return $totalPercent;
    }
}
