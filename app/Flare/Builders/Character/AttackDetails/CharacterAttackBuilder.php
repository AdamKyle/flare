<?php

namespace App\Flare\Builders\Character\AttackDetails;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Map;
use Exception;

class CharacterAttackBuilder {

    use FetchEquipped;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var CharacterStatBuilder $characterStatBuilder
     */
    private CharacterStatBuilder $characterStatBuilder;

    /**
     * @param CharacterStatBuilder $characterStatBuilder
     */
    public function __construct(CharacterStatBuilder $characterStatBuilder) {
        $this->characterStatBuilder = $characterStatBuilder;
    }

    /**
     * Set the character.
     *
     * @param Character $character
     * @return $this
     */
    public function setCharacter(Character $character): CharacterAttackBuilder {
        $this->character = $character;

        $this->characterStatBuilder = $this->characterStatBuilder->setCharacter($character);

        return $this;
    }

    /**
     * Build the characters attack.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildAttack(bool $voided = false): array {
        $attack = $this->baseAttack($voided);

        $attack['weapon_damage'] = $this->characterStatBuilder->buildDamage('weapon', $voided);

        return $attack;
    }

    /**
     * Build the characters cast attack
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildCastAttack(bool $voided = false) {
        $attack = $this->baseAttack($voided);

        $attack['spell_damage'] = $this->characterStatBuilder->buildDamage('spell-damage', $voided);

        return $attack;
    }

    /**
     * Build the characters Cast and Attack.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildCastAndAttack(bool $voided = false): array {
        return $this->castAndAttackPositionalDamage('spell-one', 'left-hand', $voided);
    }

    /**
     * Build the characters Attack and Cast.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildAttackAndCast(bool $voided = false): array {
        return $this->castAndAttackPositionalDamage('spell-two', 'right-hand', $voided);
    }

    /**
     * Build the characters defend.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildDefend(bool $voided = false): array {
        $defence = $this->baseAttack($voided);

        $defence['defence'] = $this->characterStatBuilder->buildDefence($voided);

        return $defence;
    }

    /**
     * The base attack object when building the different attack types.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    protected function baseAttack(bool $voided = false): array {
        $map     = Map::where('character_id', $this->character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        $characterReduction = $gameMap->character_attack_reduction;

        return [
            'name'                      => $this->character->name,
            'ring_damage'               => $this->characterStatBuilder->buildDamage('ring', $voided),
            'heal_for'                  => $this->characterStatBuilder->buildHealing($voided),
            'res_chance'                => $this->characterStatBuilder->buildResurrectionChance(),
            'damage_deduction'          => $characterReduction,
            'ambush_chance'             => $this->characterStatBuilder->buildAmbush('chance'),
            'ambush_resistance_chance'  => $this->characterStatBuilder->buildAmbush('resistance'),
            'counter_chance'            => $this->characterStatBuilder->buildCounter('chance'),
            'counter_resistance_chance' => $this->characterStatBuilder->buildCounter('resistance'),
            'affixes'                   => [
                'cant_be_resisted'       => $this->characterStatBuilder->canAffixesBeResisted(),
                'stacking_damage'        => $this->characterStatBuilder->buildAffixDamage('affix-stacking-damage', $voided) +
                                            $this->characterStatBuilder->buildAffixDamage('affix-irresistible-damage-stacking', $voided),
                'non_stacking_damage'    => $this->characterStatBuilder->buildAffixDamage('affix-non-stacking', $voided) +
                                            $this->characterStatBuilder->buildAffixDamage('affix-irresistible-damage-non-stacking', $voided),
                'stacking_life_stealing' => $this->characterStatBuilder->buildAffixDamage('life-stealing', $voided),
                'life_stealing'          => $this->characterStatBuilder->buildAffixDamage('life-stealing', $voided),
                'entrancing_chance'      => $this->characterStatBuilder->buildEntrancingChance($voided)
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
     * @throws Exception
     */
    protected function castAndAttackPositionalDamage(string $spellPosition, string $weaponPosition, bool $voided = false): array {
        $attack        = $this->baseAttack($voided);

        $weaponDamage = $this->characterStatBuilder->positionalWeaponDamage($weaponPosition, $voided);
        $spellDamage  = $this->characterStatBuilder->positionalSpellDamage($spellPosition, $voided);
        $spellHealing = $this->characterStatBuilder->positionalHealing($spellPosition, $voided);

        $attack['spell_damage']  = $spellDamage;
        $attack['heal_for']      = $spellHealing;
        $attack['weapon_damage'] = $weaponDamage;

        return $attack;
    }
}
