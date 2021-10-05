<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;

class CharacterAttackBuilder {

    private $character;

    private $characterInformationBuilder;

    public function __construct(CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    public function setCharacter(Character $character): CharacterAttackBuilder {
        $this->character = $character;

        $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($character);

        return $this;
    }

    public function buildAttack(): array {
        $attack = $this->baseAttack();

        $attack['weapon_damage'] = $this->characterInformationBuilder->buildAttack();

        return $attack;
    }

    public function buildCastAttack() {
        $attack = $this->baseAttack();

        $attack['spell_damage'] = $this->characterInformationBuilder->getTotalSpellDamage();

        return $attack;
    }

    public function buildCastAndAttack(): array {
        return $this->castAndAttackPositionalDamage('spell-one', 'left-hand');
    }

    public function buildAttackAndCast(): array {
        return $this->castAndAttackPositionalDamage('spell-two', 'right-hand');
    }

    public function buildDefend(): array {
        $baseAttack = $this->baseAttack();

        $ac                    = $this->characterInformationBuilder->buildDefence();
        $str                   = $this->characterInformationBuilder->statMod('str') * 0.05;

        $ac = $ac + $ac * $str;

        $baseAttack['defence'] = $ac + $ac * 0.5;

        return $baseAttack;
    }

    protected function baseAttack(): array {
        return [
            'name'            => $this->character->name,
            'defence'         => $this->characterInformationBuilder->buildDefence(),
            'ring_damage'     => $this->characterInformationBuilder->getTotalRingDamage(),
            'artifact_damage' => $this->characterInformationBuilder->getTotalArtifactDamage(),
            'heal_for'        => $this->characterInformationBuilder->buildHealFor(),
            'res_chance'      => $this->characterInformationBuilder->fetchResurrectionChance(),
            'affixes'         => [
                'cant_be_resisted'       => $this->characterInformationBuilder->canAffixesBeResisted(),
                'stacking_damage'        => $this->characterInformationBuilder->getTotalAffixDamage(),
                'non_stacking_damage'    => $this->characterInformationBuilder->getTotalAffixDamage(false),
                'stacking_life_stealing' => $this->characterInformationBuilder->findLifeStealingAffixes(true),
                'life_stealing'          => $this->characterInformationBuilder->findLifeStealingAffixes(),
                'entrancing_chance'      => $this->characterInformationBuilder->getEntrancedChance(),
            ]
        ];
    }

    protected function castAndAttackPositionalDamage(string $spellPosition, string $weaponPosition): array {
        $attack = $this->baseAttack();

        $spellSlotOne  = $this->fetchSlot($spellPosition);
        $weaponSlotOne = $this->fetchSlot($weaponPosition);

        $weaponDamage = 0;
        $spellDamage = 0;

        if (!is_null($weaponSlotOne)) {
            $weaponDamage = $weaponSlotOne->item->getTotalDamage();
        }

        if (!is_null($spellSlotOne)) {
            $spellDamage = $spellSlotOne->item->getTotalDamage();

            $bonus = $this->characterInformationBuilder->hereticSpellDamageBonus($this->character);

            $spellDamage = $spellDamage + $spellDamage * $bonus;
        }

        $attack['weapon_damage'] = $weaponDamage;
        $attack['spell_damage']  = $spellDamage;

        return $attack;
    }

    protected function fetchSlot(string $position): InventorySlot|SetSlot|null {
        return $this->characterInformationBuilder->fetchInventory()->filter(function($slot) use($position) {
            return $slot->position === $position && $slot->equipped;
        })->first();
    }

}