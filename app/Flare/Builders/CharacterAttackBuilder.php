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

    public function buildAttack(bool $voided = false): array {
        $attack = $this->baseAttack($voided);

        $attack['weapon_damage'] = $this->characterInformationBuilder->buildAttack($voided);

        return $attack;
    }

    public function buildCastAttack(bool $voided = false) {
        $attack = $this->baseAttack($voided);

        $attack['spell_damage'] = $this->characterInformationBuilder->getTotalSpellDamage($voided);

        return $attack;
    }

    public function buildCastAndAttack(bool $voided = false): array {
        return $this->castAndAttackPositionalDamage('spell-one', 'left-hand', $voided);
    }

    public function buildAttackAndCast(bool $voided = false): array {
        return $this->castAndAttackPositionalDamage('spell-two', 'right-hand', $voided);
    }

    public function buildDefend(bool $voided = false): array {
        $baseAttack = $this->baseAttack($voided);

        $ac                    = $this->characterInformationBuilder->buildDefence($voided);
        $str                   = $this->characterInformationBuilder->statMod('str') * 0.05;

        $ac = $ac + $ac * $str;

        $baseAttack['defence'] = $ac + $ac * 0.5;

        return $baseAttack;
    }

    protected function baseAttack(bool $voided = false): array {
        return [
            'name'            => $this->character->name,
            'defence'         => $this->characterInformationBuilder->buildDefence($voided),
            'ring_damage'     => $this->characterInformationBuilder->getTotalRingDamage($voided),
            'artifact_damage' => $this->characterInformationBuilder->getTotalArtifactDamage($voided),
            'heal_for'        => $this->characterInformationBuilder->buildHealFor($voided),
            'res_chance'      => $this->characterInformationBuilder->fetchResurrectionChance(),
            'affixes'         => [
                'cant_be_resisted'       => $this->characterInformationBuilder->canAffixesBeResisted(),
                'stacking_damage'        => $voided ? 0 : $this->characterInformationBuilder->getTotalAffixDamage(),
                'non_stacking_damage'    => $voided ? 0 : $this->characterInformationBuilder->getTotalAffixDamage(false),
                'stacking_life_stealing' => $voided ? 0 : $this->characterInformationBuilder->findLifeStealingAffixes(true),
                'life_stealing'          => $voided ? 0 : $this->characterInformationBuilder->findLifeStealingAffixes(),
                'entrancing_chance'      => $voided ? 0 : $this->characterInformationBuilder->getEntrancedChance(),
            ]
        ];
    }

    protected function castAndAttackPositionalDamage(string $spellPosition, string $weaponPosition, bool $voided = false): array {
        $attack = $this->baseAttack();

        $spellSlotOne  = $this->fetchSlot($spellPosition);
        $weaponSlotOne = $this->fetchSlot($weaponPosition);

        $weaponDamage = 0;
        $spellDamage = 0;

        if (!is_null($weaponSlotOne)) {
            if (!$voided) {
                $weaponDamage = $weaponSlotOne->item->getTotalDamage();
            } else {
                $weaponDamage = $weaponSlotOne->item->base_damage;
            }
        }

        if (!is_null($spellSlotOne)) {
            if (!$voided) {
                $spellDamage = $spellSlotOne->item->getTotalDamage();
            } else {
                $spellDamage = $spellSlotOne->item->base_damage;
            }

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