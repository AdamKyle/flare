<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;

class CharacterAttackBuilder {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var CharacterInformationBuilder $characterInformationBuilder
     */
    private $characterInformationBuilder;

    /**
     * @param CharacterInformationBuilder $characterInformationBuilder
     */
    public function __construct(CharacterInformationBuilder $characterInformationBuilder) {
        $this->characterInformationBuilder = $characterInformationBuilder;
    }

    /**
     * Set the character.
     *
     * @param Character $character
     * @return $this
     */
    public function setCharacter(Character $character): CharacterAttackBuilder {
        $this->character = $character;

        $this->characterInformationBuilder = $this->characterInformationBuilder->setCharacter($character);

        return $this;
    }

    /**
     * Build the characters attack.
     *
     * @param bool $voided
     * @return array
     */
    public function buildAttack(bool $voided = false): array {
        $attack = $this->baseAttack($voided);

        $attack['weapon_damage'] = $this->characterInformationBuilder->getTotalWeaponDamage($voided);

        return $attack;
    }

    /**
     * Build the characters cast attack
     *
     * @param bool $voided
     * @return array
     */
    public function buildCastAttack(bool $voided = false) {
        $attack = $this->baseAttack($voided);

        $attack['spell_damage'] = $this->characterInformationBuilder->getTotalSpellDamage($voided);

        return $attack;
    }

    /**
     * Build the characters Cast and Attack.
     *
     * @param bool $voided
     * @return array
     */
    public function buildCastAndAttack(bool $voided = false): array {
        return $this->castAndAttackPositionalDamage('spell-one', 'left-hand', $voided);
    }

    /**
     * Build the characters Attack and Cast.
     *
     * @param bool $voided
     * @return array
     */
    public function buildAttackAndCast(bool $voided = false): array {
        return $this->castAndAttackPositionalDamage('spell-two', 'right-hand', $voided);
    }

    /**
     * Build the characters defend.
     *
     * @param bool $voided
     * @return array
     * @throws \Exception
     */
    public function buildDefend(bool $voided = false): array {
        $baseAttack = $this->baseAttack($voided);

        $ac                    = $this->characterInformationBuilder->buildDefence($voided);
        $str                   = $this->characterInformationBuilder->statMod('str') * 0.05;

        if ($voided) {
            $str = $this->character->str * 0.05;
        }

        if ($this->character->classType()->isFighter()) {
            $str = $this->characterInformationBuilder->statMod('str') * 0.15;

            if ($voided) {
                $str = $this->character->str * 0.15;
            }
        }

        $ac = ceil($ac + $ac * $str);

        $baseAttack['defence'] = $ac;

        return $baseAttack;
    }

    /**
     * Get the information builder instance.
     *
     * @return CharacterInformationBuilder
     */
    public function getInformationBuilder(): CharacterInformationBuilder {
        return $this->characterInformationBuilder;
    }

    /**
     * Get positional weapon damage, from either left or right hand.
     *
     * @param string $hand
     * @param bool $voided
     * @return float
     */
    public function getPositionalWeaponDamage(string $hand, bool $voided = false) {
        $weaponSlotOne = $this->fetchSlot($hand);

        $weaponDamage = 0;

        if (!is_null($weaponSlotOne)) {
            if (!$voided) {
                $weaponDamage = $weaponSlotOne->item->getTotalDamage();
            } else {
                $weaponDamage = $weaponSlotOne->item->base_damage;
            }
        }

        if (is_null($weaponDamage)) {
            $weaponDamage = 0;
        }

        return ceil($this->characterInformationBuilder->calculateWeaponDamage($weaponDamage, $voided));
    }

    /**
     * The base attack object when building the different attack types.
     *
     * @param bool $voided
     * @return array
     * @throws \Exception
     */
    protected function baseAttack(bool $voided = false): array {
        $enemyStatBonus = $this->character->map->gameMap->enemy_stat_bonus;

        return [
            'name'             => $this->character->name,
            'defence'          => $this->characterInformationBuilder->buildDefence($voided),
            'ring_damage'      => $this->characterInformationBuilder->getTotalRingDamage($voided),
            'artifact_damage'  => $voided ? 0 : $this->characterInformationBuilder->getTotalArtifactDamage(),
            'heal_for'         => $this->characterInformationBuilder->buildHealFor($voided),
            'res_chance'       => $this->characterInformationBuilder->fetchResurrectionChance(),
            'damage_deduction' => is_null($enemyStatBonus) ? 0.0 : $enemyStatBonus,
            'affixes'          => [
                'cant_be_resisted'       => $this->characterInformationBuilder->canAffixesBeResisted(),
                'stacking_damage'        => $voided ? 0 : $this->characterInformationBuilder->getTotalAffixDamage(),
                'non_stacking_damage'    => $voided ? 0 : $this->characterInformationBuilder->getTotalAffixDamage(false),
                'stacking_life_stealing' => $voided ? 0 : $this->characterInformationBuilder->findLifeStealingAffixes(true),
                'life_stealing'          => $voided ? 0 : $this->characterInformationBuilder->findLifeStealingAffixes(),
                'entrancing_chance'      => $voided ? 0 : $this->characterInformationBuilder->getEntrancedChance(),
            ]
        ];
    }

    /**
     * Deals with the positional aspects of Attack and Cast and Cast and Attack.
     *
     * @param string $spellPosition
     * @param string $weaponPosition
     * @param bool $voided
     * @return array
     * @throws \Exception
     */
    protected function castAndAttackPositionalDamage(string $spellPosition, string $weaponPosition, bool $voided = false): array {
        $attack = $this->baseAttack($voided);

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

        if (is_null($weaponDamage)) {
            $weaponDamage = 0;
        } else {
            $weaponDamage = $this->characterInformationBuilder->damageModifiers($weaponDamage, $voided);
        }

        $weaponDamage = $this->characterInformationBuilder->calculateWeaponDamage($weaponDamage, $voided);

        if (!is_null($spellSlotOne)) {
            if ($spellSlotOne->item->type === 'spell-damage') {
                if (!$voided) {
                    $spellDamage = $spellSlotOne->item->getTotalDamage();
                } else {
                    $spellDamage = $spellSlotOne->item->base_damage;
                }

                $bonus = $this->characterInformationBuilder->hereticSpellDamageBonus($this->character);

                $spellDamage = $this->characterInformationBuilder->calculateClassSpellDamage($spellDamage, $voided);

                $spellDamage = $spellDamage + $spellDamage * $bonus;
            }

            if ($spellSlotOne->item->type === 'spell-healing') {
                $spellDamage = $this->characterInformationBuilder->buildHealFor($voided);
            }

            if ($spellSlotOne->item->type === 'spell-damage') {
                $attack['spell_damage'] = $spellDamage;
                $attack['heal_for']     = 0;
            } else {
                $attack['heal_for']     = $spellDamage;
                $attack['spell_damage'] = 0;
            }
        } else {
            $attack['spell_damage']  = 0;
            $attack['heal_for']      = 0;
        }

        $attack['weapon_damage'] = $weaponDamage;

        return $attack;
    }

    /**
     * Fetches a specific slot or returns null.
     *
     * Because characters can have inventory sets, the slot could be a set slot or
     * a regular inventory slot.
     *
     * @param string $position
     * @return InventorySlot|SetSlot|null
     */
    protected function fetchSlot(string $position): InventorySlot|SetSlot|null {
        $slot = $this->characterInformationBuilder->fetchInventory()->filter(function($slot) use($position) {
            return $slot->position === $position && $slot->equipped;
        })->first();

        // Check to see if the user is holding a bow.
        if (is_null($slot) && ($position === 'left-hand' || $position === 'right-hand')) {
            $slot = $this->characterInformationBuilder->fetchInventory()->filter(function($slot) use($position) {
                return $slot->item->type === 'bow';
            })->first();
        }

        return $slot;
    }

}