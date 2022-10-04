<?php

namespace App\Flare\Builders\Character\ClassDetails;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;

class HolyStacks {

    use FetchEquipped;

    public function fetchHolyBonus(Character $character): float {
        return $this->fetchTotalHolyStacks($character) / $this->fetchTotalStacksForCharacter($character);
    }

    public function fetchDevouringResistanceBonus(Character $character): float {
        return $this->fetchHolyBonus($character);
    }

    public function fetchAttackBonus(Character $character): float {
        $holyBonus = $this->fetchHolyBonus($character);

        if ($holyBonus > 0.90) {
            return 0.90;
        }

        return $holyBonus;
    }

    public function fetchDefenceBonus(Character $character): float {
        $holyBonus = $this->fetchHolyBonus($character);

        if ($holyBonus > 0.75) {
            return 0.75;
        }

        return $holyBonus;
    }

    public function fetchHealingBonus(Character $character) {
        return $this->fetchTotalHolyStacks($character) / 100;
    }

    public function fetchTotalStacksForCharacter(Character $character): int {
        if ($character->classType()->isRanger() || $character->classType()->isBlacksmith() || $character->classType()->isArcaneAlchemist()) {
            return 220;
        }

        return 240;
    }

    public function fetchTotalHolyStacks(Character $character): int {
        $slots = $this->fetchEquipped($character);

        if (is_null($slots)) {
            return 0;
        }

        return $slots->sum('item.holy_stacks_applied');
    }
}
