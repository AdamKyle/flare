<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;


class HolyBuilder extends BaseAttribute {

    public function fetchHolyBonus(): float {
        if (is_null($this->inventory)) {
            return 0;
        }

        return $this->getTotalAppliedStacks() / $this->fetchTotalStacksForCharacter();
    }

    public function fetchDevouringResistanceBonus(): float {
        if (is_null($this->inventory)) {
            return 0;
        }

        $total = $this->getTotalAmount('devouring_darkness_bonus');

        return min($total, 1);
    }

    public function fetchStatIncrease(): float {
        if (is_null($this->inventory)) {
            return 0;
        }

        return $this->getTotalAmount('stat_increase_bonus');
    }

    public function fetchAttackBonus(): float {
        $holyBonus = $this->fetchHolyBonus();

        return min($holyBonus, .90);
    }

    public function fetchDefenceBonus(): float {
        $holyBonus = $this->fetchHolyBonus();

        return min($holyBonus, .75);
    }

    public function fetchHealingBonus(): float {
        $value = $this->getTotalAppliedStacks() / 100;

        return min($value, 1.0);
    }

    public function fetchTotalStacksForCharacter(): int {
        if ($this->character->classType()->isRanger() ||
            $this->character->classType()->isBlacksmith() ||
            $this->character->classType()->isArcaneAlchemist())
        {
            return 220;
        }

        return 240;
    }

    public function getTotalAppliedStacks(): int {

        if (is_null($this->inventory)) {
            return 0;
        }

        return $this->inventory->sum('item.holy_stacks_applied');
    }

    protected function getTotalAmount(string $type): float {
        $items = $this->inventory->where('item.type', '!=', 'trinket');
        $bonus = 0;

        foreach ($items as $slot) {
            $bonus += $slot->item->appliedHolyStacks->sum($type);
        }

        return $bonus;
    }


}
