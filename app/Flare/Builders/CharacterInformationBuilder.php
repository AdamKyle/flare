<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Values\MaxDamageForItemValue;

class CharacterInformationBuilder {

    public function setCharacter(Character $character): CharacterInformationBuilder {
        $this->character = $character;

        return $this;
    }

    public function buildAttack(): int {
        return ($this->character->{$this->character->damage_stat} + 10) + $this->getWeaponDamage();
    }

    public function buildHealth(): int {
        return $this->character->dur + 10;
    }

    protected function getWeaponDamage(): int {
        $leftHand  = $this->character->equippedItems->where('type', '=', 'left-hand')->first();
        $rightHand = $this->character->equippedItems->where('type', '=', 'right-hand')->first();

        if (!is_null($leftHand) && !is_null($rightHand)) {
            return resolve(MaxDamageForItemValue::class)->fetchMaxDamage($leftHand->item) +
                   resolve(MaxDamageForItemValue::class)->fetchMaxDamage($rightHand->item);
        }

        if (!is_null($leftHand)) {
            return resolve(MaxDamageForItemValue::class)->fetchMaxDamage($leftHand->item);
        }

        if (!is_null($rightHand)) {
            return resolve(MaxDamageForItemValue::class)->fetchMaxDamage($rightHand->item);
        }

        return 0;
    }
}
