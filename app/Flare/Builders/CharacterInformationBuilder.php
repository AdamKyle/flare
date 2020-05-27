<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\MaxDamageForItemValue;

class CharacterInformationBuilder {

    private $character;

    private $inventory;

    public function setCharacter(Character $character): CharacterInformationBuilder {
        $this->character = $character;

        $this->inventory = $character->inventory->slots->where('equipped', true);

        return $this;
    }

    public function statMod(string $stat): float {

        $base = $this->character->{$stat};

        $equipped = $this->inventory->filter(function($slot) {
            return $slot->equipped;
        });

        if ($equipped->isEmpty()) {
            return $base;
        }

        foreach ($equipped as $slot) {
            $percentageIncrease = $this->fetchModdedStat($stat, $slot->item);

            if ($percentageIncrease !== 0.0) {
                $base += ($base * $this->fetchModdedStat($stat, $slot->item));
            }
        }

        return $base;
    }

    public function buildAttack(): int {
        return ($this->character->{$this->character->damage_stat} + 10) + $this->getWeaponDamage();
    }

    public function buildDefence(): int {
        return 10 + $this->getDefence();
    }

    public function buildHealFor(): int {
        return $this->fetchHealingAmount();
    }

    public function buildHealth(): int {
        return $this->character->dur + 10;
    }

    public function hasArtifacts(): bool {
        return $this->inventory->filter(function ($slot) {
            return $slot->item->type === 'artifact';
        })->isNotEmpty();
    }

    public function hasAffixes(): bool {
        return true;
    }

    public function hasSpells(): bool {
        return $this->inventory->filter(function ($slot) {
            return $slot->item->type === 'spell';
        })->isNotEmpty();
    }

    protected function getWeaponDamage(): int {
        $damage = 0;

        foreach ($this->inventory as $slot) {
            $damage += $slot->item->getTotalDamage();
        }

        return $damage;
    }

    protected function getDefence(): int {
        $defence = 0;

        foreach ($this->inventory as $slot) {
            $defence += $slot->item->getTotalDefence();
        }

        return $defence;
    }

    protected function fetchHealingAmount(): int {
        $healFor = 0;

        foreach ($this->inventory as $slot) {
            $healFor += $slot->item->getTotalHealing();
        }

        return $healFor;
    }

    protected function fetchModdedStat(string $stat, Item $item): float {
        $staMod          = $item->{$stat . '_mod'};
        $totalPercentage = !is_null($staMod) ? $staMod : 0.0;

        if (!is_null($item->itemPrefix)) {
            $prefixMod        = $item->itemPrefix->{$stat . '_mod'};
            $totalPercentage += !is_null($prefixMod) ? $prefixMod : 0.0;
        }

        if (!is_null($item->itemSuffix)) {
            $suffixMod        = $item->itemSuffix->{$stat . '_mod'};
            $totalPercentage += !is_null($suffixMod) ? $suffixMod : 0.0;
        }

        return  $totalPercentage;
    }
}
