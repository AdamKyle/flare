<?php

namespace App\Game\Character\Builders\AttackBuilders\AttackDetails;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Map;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Values\GemAbilityEffectType;
use App\Game\Gems\Values\ResolvedCharacterGemEffects;
use Exception;

class CharacterAttackBuilder
{
    use FetchEquipped;

    private Character $character;

    private CharacterStatBuilder $characterStatBuilder;

    private ?float $damageStatAmount = null;

    private float $areaGemCharacterPowerReduction = 0.0;

    private ResolvedCharacterGemEffects $characterGemEffects;

    public function __construct(
        CharacterStatBuilder $characterStatBuilder,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
        private readonly CharacterGemEffects $characterGemEffectService,
    ) {
        $this->characterStatBuilder = $characterStatBuilder;
    }

    /**
     * Set the Character and resolve its Area and character Gem effects for attack building.
     *
     * @param Character $character
     * @param bool $ignoreReductions
     * @param float|null $damageStatAmount
     * @return CharacterAttackBuilder
     */
    public function setCharacter(
        Character $character,
        bool $ignoreReductions = false,
        ?float $damageStatAmount = null,
        ?ResolvedCharacterGemEffects $resolvedCharacterGemEffects = null,
    ): CharacterAttackBuilder {
        $this->character = $character;
        $this->damageStatAmount = $damageStatAmount;
        $this->characterGemEffects = $resolvedCharacterGemEffects
            ?? $this->characterGemEffectService->resolveForCharacterId($character->id);

        $this->areaGemCharacterPowerReduction = $ignoreReductions
            ? 0.0
            : $this->characterAreaGemEffectService->resolveForCharacter($character)->characterPowerReduction();

        $this->characterStatBuilder = $this->characterStatBuilder->setCharacter(
            $character,
            $ignoreReductions,
            $this->characterGemEffects,
        );

        return $this;
    }

    /**
     * Build the cached weapon attack payload.
     *
     * @param bool $voided
     * @return array
     *
     * @throws Exception
     */
    public function buildAttack(bool $voided = false): array
    {
        $attack = $this->baseAttack(AttackType::ATTACK->value, $voided);

        $attack['weapon_damage'] = $this->applyPassiveAbility(
            $this->characterStatBuilder->buildDamage(ItemType::validWeapons(), $voided),
            GemAbilityEffectType::WEAPON_DAMAGE_MOD,
            AttackType::ATTACK->value,
        );

        return $attack;
    }

    /**
     * Build the cached cast attack payload.
     *
     * @param bool $voided
     * @return array
     *
     * @throws Exception
     */
    public function buildCastAttack(bool $voided = false): array
    {
        $attack = $this->baseAttack(AttackType::CAST->value, $voided);

        $attack['spell_damage'] = $this->applyPassiveAbility($this->characterStatBuilder->buildDamage('spell-damage', $voided), GemAbilityEffectType::SPELL_DAMAGE_MOD, AttackType::CAST->value);
        $attack['heal_for'] = $this->applyPassiveAbility($this->characterStatBuilder->buildHealing($voided), GemAbilityEffectType::HEALING_MOD, AttackType::CAST->value);

        return $attack;
    }

    /**
     * Build the cached cast-and-attack payload.
     *
     * @param bool $voided
     * @return array
     *
     * @throws Exception
     */
    public function buildCastAndAttack(bool $voided = false): array
    {
        return $this->castAndAttackPositionalDamage(AttackType::CAST_AND_ATTACK->value, 'spell-one', 'right-hand', $voided);
    }

    /**
     * Build the cached attack-and-cast payload.
     *
     * @param bool $voided
     * @return array
     *
     * @throws Exception
     */
    public function buildAttackAndCast(bool $voided = false): array
    {
        return $this->castAndAttackPositionalDamage(AttackType::ATTACK_AND_CAST->value, 'spell-two', 'left-hand', $voided);
    }

    /**
     * Build the cached defend payload.
     *
     * @param bool $voided
     * @return array
     *
     * @throws Exception
     */
    public function buildDefend(bool $voided = false): array
    {
        $defence = $this->baseAttack(AttackType::DEFEND->value, $voided);

        $defence['defence'] = $this->applyPassiveAbility($this->characterStatBuilder->buildDefence($voided), GemAbilityEffectType::DEFENCE_MOD, AttackType::DEFEND->value);

        return $defence;
    }

    /**
     * Build fields shared by every cached top-level attack action.
     *
     * @param string $attackType
     * @param bool $voided
     * @return array
     *
     * @throws Exception
     */
    private function baseAttack(string $attackType, bool $voided = false): array
    {
        $map = Map::where('character_id', $this->character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        $characterReduction = ($gameMap->character_attack_reduction ?? 0.0)
            + $this->areaGemCharacterPowerReduction;

        return [
            'attack_type' => $attackType,
            'name' => $this->character->name,
            'weapon_damage' => $this->characterStatBuilder->buildDamage(ItemType::validWeapons(), $voided),
            'spell_damage' => $this->characterStatBuilder->buildDamage(ItemType::SPELL_DAMAGE->value, $voided),
            'defence' => $this->characterStatBuilder->buildDefence($voided),
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
            'damage_stat_amount' => $this->damageStatAmount ?? $this->characterStatBuilder->statMod($this->character->damage_stat, $voided),
            'gem_abilities' => [
                'active' => $this->abilitiesForAction($this->characterGemEffects->activeAbilitySnapshots(), $attackType),
                'passive' => $this->abilitiesForAction($this->characterGemEffects->passiveAbilitySnapshots(), $attackType),
            ],
        ];
    }

    /**
     * Build effective equipped Class Mastery specialty damage details.
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

        $damageStatAmount = $this->damageStatAmount ?? $this->character->getInformation()->statMod($this->character->damage_stat);

        $baseDamage = $classSpecialEquipped->gameClassSpecial->specialty_damage;
        $addedDamage = $classSpecialEquipped->gameClassSpecial->increase_specialty_damage_per_level * $classSpecialEquipped->level;
        $damage = (
            $baseDamage
            + $addedDamage
            + $damageStatAmount * $classSpecialEquipped->gameClassSpecial->specialty_damage_uses_damage_stat_amount
        ) * (1 + $this->characterGemEffects->classMasteryEffect());

        return [
            'name' => $classSpecialEquipped->gameClassSpecial->name,
            'damage' => $damage,
            'required_attack_type' => $classSpecialEquipped->gameClassSpecial->attack_type_required,
        ];
    }

    /**
     * Build positional weapon, spell, and healing values for one mixed action.
     *
     * @param string $attackType
     * @param string $spellPosition
     * @param string $weaponPosition
     * @param bool $voided
     * @return array
     *
     * @throws Exception
     */
    private function castAndAttackPositionalDamage(string $attackType, string $spellPosition, string $weaponPosition, bool $voided = false): array
    {
        $attack = $this->baseAttack($attackType, $voided);

        $weaponDamage = $this->characterStatBuilder->positionalWeaponDamage($weaponPosition, $voided);
        $spellDamage = $this->characterStatBuilder->positionalSpellDamage($spellPosition, $voided);
        $spellHealing = $this->characterStatBuilder->positionalHealing($spellPosition, $voided);

        $attack['spell_damage'] = $this->applyPassiveAbility($spellDamage, GemAbilityEffectType::SPELL_DAMAGE_MOD, $attackType);
        $attack['heal_for'] = $this->applyPassiveAbility($spellHealing, GemAbilityEffectType::HEALING_MOD, $attackType);
        $attack['weapon_damage'] = $this->applyPassiveAbility($weaponDamage, GemAbilityEffectType::WEAPON_DAMAGE_MOD, $attackType);

        return $attack;
    }

    /**
     * Apply action-specific passive Gem Ability bonuses to one cached combat value.
     *
     * @param int $value
     * @param GemAbilityEffectType $effectType
     * @param string $attackType
     * @return int
     */
    private function applyPassiveAbility(int $value, GemAbilityEffectType $effectType, string $attackType): int
    {
        return floor($value * (1 + $this->characterGemEffects->passiveBonusFor($effectType, $attackType)));
    }

    /**
     * Return cached Gem Ability snapshots allowed for the selected base action.
     *
     * @param array $abilities
     * @param string $attackType
     * @return array
     */
    private function abilitiesForAction(array $abilities, string $attackType): array
    {
        return array_values(array_filter(
            $abilities,
            fn (array $ability): bool => in_array($attackType, $ability['attack_types'], true),
        ));
    }
}
