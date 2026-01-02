<?php

namespace App\Game\Character\Builders\AttackBuilders\AttackDetails;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Map;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Concerns\FetchEquipped;
use Exception;

class CharacterAttackBuilder
{
    use FetchEquipped;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @param CharacterStatBuilder $characterStatBuilder
     */
    public function __construct(private CharacterStatBuilder $characterStatBuilder)
    {}

    /**
     * Set the character.
     *
     * @param Character $character
     * @param bool $ignoreReductions
     * @return $this
     */
    public function setCharacter(Character $character, bool $ignoreReductions = false): CharacterAttackBuilder
    {
        $this->character = $character;

        $this->characterStatBuilder = $this->characterStatBuilder->setCharacter($character, $ignoreReductions);

        return $this;
    }

    /**
     * Build the characters attack.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildAttack(bool $voided = false): array
    {
        $attack = $this->baseAttack(AttackTypeValue::ATTACK, $voided);

        $attack['weapon_damage'] = $this->characterStatBuilder->buildDamage(ItemType::validWeapons(), $voided);

        return $attack;
    }

    /**
     * Build the characters cast attack
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildCastAttack(bool $voided = false)
    {
        $attack = $this->baseAttack(AttackTypeValue::CAST, $voided);

        $attack['spell_damage'] = $this->characterStatBuilder->buildDamage('spell-damage', $voided);
        $attack['heal_for'] = $this->characterStatBuilder->buildHealing($voided);

        return $attack;
    }

    /**
     * Build the characters Cast and Attack.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildCastAndAttack(bool $voided = false): array
    {
        return $this->castAndAttackPositionalDamage(AttackTypeValue::CAST_AND_ATTACK, 'spell-one', 'right-hand', $voided);
    }

    /**
     * Build the characters Attack and Cast.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildAttackAndCast(bool $voided = false): array
    {
        return $this->castAndAttackPositionalDamage(AttackTypeValue::ATTACK_AND_CAST, 'spell-two', 'left-hand', $voided);
    }

    /**
     * Build the characters defend.
     *
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    public function buildDefend(bool $voided = false): array
    {
        $defence = $this->baseAttack(AttackTypeValue::DEFEND, $voided);

        $defence['defence'] = $this->characterStatBuilder->buildDefence($voided);

        return $defence;
    }

    /**
     * The base attack object when building the different attack types.
     *
     * @param string $attackType
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    private function baseAttack(string $attackType, bool $voided = false): array
    {
        $map = Map::where('character_id', $this->character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        $characterReduction = $gameMap->character_attack_reduction;

        return [
            'attack_type' => $attackType,
            'name' => $this->character->name,
            'ring_damage' => $this->characterStatBuilder->buildDamage(ItemType::RING->value, $voided),
            'heal_for' => $this->characterStatBuilder->buildHealing($voided),
            'res_chance' => $this->characterStatBuilder->buildResurrectionChance(),
            'damage_deduction' => $characterReduction,
            'ambush_chance' => $this->characterStatBuilder->buildAmbush(),
            'ambush_resistance_chance' => $this->characterStatBuilder->buildAmbush('resistance'),
            'counter_chance' => $this->characterStatBuilder->buildCounter(),
            'counter_resistance_chance' => $this->characterStatBuilder->buildCounter('resistance'),
            'affixes' => [
                'cant_be_resisted' => $this->characterStatBuilder->canAffixesBeResisted(),
                'stacking_damage' => $this->characterStatBuilder->buildAffixDamage('affix-stacking-damage', $voided),
                'non_stacking_damage' => $this->characterStatBuilder->buildAffixDamage('affix-non-stacking', $voided),
                'stacking_life_stealing' => $this->characterStatBuilder->buildAffixDamage('life-stealing', $voided),
                'life_stealing' => $this->characterStatBuilder->buildAffixDamage('life-stealing', $voided),
                'entrancing_chance' => $this->characterStatBuilder->buildEntrancingChance($voided),
            ],
            'special_damage' => $this->fetchClassSpecialDamageInfo(),
        ];
    }

    /**
     * Builds the special damage information.
     *
     * - Based off the class special equipped which does damage.
     *
     * @return array
     */
    private function fetchClassSpecialDamageInfo(): array
    {
        $classSpecialEquipped = $this->character->classSpecialsEquipped()->where('equipped', true)->whereHas('gameClassSpecial', function ($query) {
            $query->where('specialty_damage', '>', 0);
        })->first();

        if (is_null($classSpecialEquipped)) {
            return [];
        }

        return [
            'name' => $classSpecialEquipped->gameClassSpecial->name,
            'damage' => $classSpecialEquipped->specialty_damage,
            'required_attack_type' => $classSpecialEquipped->gameClassSpecial->attack_type_required,
        ];
    }

    /**
     * Deals with the positional aspects of Attack and Cast and Cast and Attack.
     *
     * @param string $attackType
     * @param string $spellPosition
     * @param string $weaponPosition
     * @param bool $voided
     * @return array
     * @throws Exception
     */
    private function castAndAttackPositionalDamage(string $attackType, string $spellPosition, string $weaponPosition, bool $voided = false): array
    {
        $attack = $this->baseAttack($attackType, $voided);

        $weaponDamage = $this->characterStatBuilder->positionalWeaponDamage($weaponPosition, $voided);
        $spellDamage = $this->characterStatBuilder->positionalSpellDamage($spellPosition, $voided);
        $spellHealing = $this->characterStatBuilder->positionalHealing($spellPosition, $voided);

        $attack['spell_damage'] = $spellDamage;
        $attack['heal_for'] = $spellHealing;
        $attack['weapon_damage'] = $weaponDamage;

        return $attack;
    }
}
