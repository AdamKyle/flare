<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Values\MaxDamageForItemValue;

class CharacterInformationBuilder {

    private $character;

    private $inventory;

    public function setCharacter(Character $character): CharacterInformationBuilder {
        $this->character = $character;

        $this->inventory = $character->inventory;

        return $this;
    }

    public function buildAttack(): int {
        return ($this->character->{$this->character->damage_stat} + 10) + $this->getWeaponDamage();
    }

    public function buildHealth(): int {
        return $this->character->dur + 10;
    }

    public function hasArtifacts(): bool {
        return $this->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'artifact' && $slot->equipped;
        })->isNotEmpty();
    }

    public function hasAffixes(): bool {
        return true;
    }

    public function hasSpells(): bool {
        return $this->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'spell' && $slot->equipped;
        })->isNotEmpty();
    }

    protected function getWeaponDamage(): int {
        $leftHand  = $this->inventory->slots->where('position', '=', 'left-hand')->first();
        $rightHand = $this->inventory->slots->where('position', '=', 'right-hand')->first();

        if (!is_null($leftHand) && !is_null($rightHand)) {
            
            return $leftHand->item->getTotalDamage() + $rightHand->item->getTotalDamage();
        }

        if (!is_null($leftHand)) {
            return $leftHand->item->getTotalDamage();
        }

        if (!is_null($rightHand)) {
            return $rightHand->item->getTotalDamage();
        }

        return 0;
    }
}
