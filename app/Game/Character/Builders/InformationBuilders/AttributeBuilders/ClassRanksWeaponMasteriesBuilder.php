<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Exception;

class ClassRanksWeaponMasteriesBuilder extends BaseAttribute {

    public function determineBonusForWeapon(string $position = 'both'): float {

        if ($position !== 'both') {
            $slot = $this->inventory->where('position', $position)->first();


            return $this->getPercentage($slot);
        }

        $slotFromRightHand = $this->inventory->where('position', 'right-hand')->first();
        $slotFromLeftHand  = $this->inventory->where('position', 'left-hand')->first();

        $percentageForLeftHand  = $this->getPercentage($slotFromLeftHand);
        $percentageForRightHand = $this->getPercentage($slotFromRightHand);

        return $percentageForLeftHand + $percentageForRightHand;
    }

    public function determineBonusForSpellDamage(string $position = 'both'): float {
        if ($position !== 'both') {
            $slot = $this->inventory->where('position', $position)->where('item.type', 'spell-damage')->first();


            return $this->getPercentage($slot);
        }

        $spellSlotOne = $this->inventory->where('position', 'spell-one')->where('item.type', 'spell-damage')->first();
        $spellSlotTwo = $this->inventory->where('position', 'spell-two')->where('item.type', 'spell-damage')->first();

        if ($this->isTheSameTypeEquipped($spellSlotOne, $spellSlotTwo)) {
            return $this->getPercentage($spellSlotTwo);
        }

        $percentageForLeftHand  = $this->getPercentage($spellSlotOne);
        $percentageForRightHand = $this->getPercentage($spellSlotTwo);


        return $percentageForLeftHand + $percentageForRightHand;
    }

    public function determineBonusForSpellHealing(string $position = 'both'): float {
        if ($position !== 'both') {
            $slot = $this->inventory->where('position', $position)->where('item.type', 'spell-healing')->first();


            return $this->getPercentage($slot);
        }

        $spellSlotOne = $this->inventory->where('position', 'spell-one')->where('item.type', 'spell-healing')->first();
        $spellSlotTwo = $this->inventory->where('position', 'spell-two')->where('item.type', 'spell-healing')->first();

        if ($this->isTheSameTypeEquipped($spellSlotOne, $spellSlotTwo)) {
            return $this->getPercentage($spellSlotTwo);
        }

        $percentageForLeftHand  = $this->getPercentage($spellSlotOne);
        $percentageForRightHand = $this->getPercentage($spellSlotTwo);

        return $percentageForLeftHand + $percentageForRightHand;
    }

    protected function getPercentage(InventorySlot|SetSlot $slot = null): float {
        if (is_null($slot)) {
            return 0.0;
        }

        try {
            $weaponMasteryType = WeaponMasteryValue::getNumericValueForStringType($slot->item->type);
            $classRank         = $this->character->classRanks->where('game_class_id', $this->character->game_class_id)->first();

            if (is_null($classRank)) {
                return 0.0;
            }

            $weaponMastery = $classRank->weaponMasteries->where('weapon_type', $weaponMasteryType)->where('character_class_rank_id', $classRank->id)->first();

            return $weaponMastery->level / 100;
        } catch (Exception $e) {
            return 0.0;
        }
    }

    protected function isTheSameTypeEquipped(
        InventorySlot|SetSlot $slotOne = null,
        InventorySlot|SetSlot $slotTwo = null
    ): bool {

        if (is_null($slotOne)) {
            return false;
        }

        if (is_null($slotTwo)) {
            return false;
        }


        return $slotOne->item->type === $slotTwo->item->type;
    }
}
