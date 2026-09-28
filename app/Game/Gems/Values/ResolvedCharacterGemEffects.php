<?php

namespace App\Game\Gems\Values;

class ResolvedCharacterGemEffects
{
    /**
     * @param array $amounts
     * @param array $activeAbilities
     * @param array $passiveAbilities
     * @param array $gemDetails
     */
    public function __construct(
        private readonly array $amounts = [],
        private readonly array $activeAbilities = [],
        private readonly array $passiveAbilities = [],
        private readonly array $gemDetails = [],
    ) {}

    /**
     * Return the resolved amount for a modifier type.
     *
     * @param CharacterGemModifierType $modifierType
     * @return float
     */
    public function amount(CharacterGemModifierType $modifierType): float
    {
        return $this->amounts[$modifierType->value] ?? 0.0;
    }

    /**
     * Return the hard raw-stat bonus for a persisted Character stat key.
     *
     * @param string $statKey
     * @return float
     */
    public function rawStat(string $statKey): float
    {
        $modifierType = CharacterGemModifierType::fromStatKey($statKey);

        return is_null($modifierType) ? 0.0 : $this->amount($modifierType);
    }

    /**
     * Return the capped Tier 2 weapon damage modifier.
     *
     * @return float
     */
    public function baseDamageModifier(): float
    {
        return $this->amount(CharacterGemModifierType::BASE_DAMAGE_MOD);
    }

    /**
     * Return the capped Tier 2 defence modifier.
     *
     * @return float
     */
    public function baseAcModifier(): float
    {
        return $this->amount(CharacterGemModifierType::BASE_AC_MOD);
    }

    /**
     * Return the capped Tier 2 healing modifier.
     *
     * @return float
     */
    public function baseHealingModifier(): float
    {
        return $this->amount(CharacterGemModifierType::BASE_HEALING_MOD);
    }

    /**
     * Return the capped Tier 2 spell damage modifier.
     *
     * @return float
     */
    public function baseSpellDamageModifier(): float
    {
        return $this->amount(CharacterGemModifierType::BASE_SPELL_DAMAGE_MOD);
    }

    /**
     * Return the capped Class Rank XP gain.
     *
     * @return float
     */
    public function classRankXpGain(): float
    {
        return $this->amount(CharacterGemModifierType::CLASS_RANK_XP_GAIN);
    }

    /**
     * Return the capped Class Mastery XP gain.
     *
     * @return float
     */
    public function classMasteryXpGain(): float
    {
        return $this->amount(CharacterGemModifierType::CLASS_MASTERY_XP_GAIN);
    }

    /**
     * Return the capped Weapon Mastery XP gain.
     *
     * @return float
     */
    public function weaponMasteryXpGain(): float
    {
        return $this->amount(CharacterGemModifierType::WEAPON_MASTERY_XP_GAIN);
    }

    /**
     * Return the capped matching class-skill XP gain.
     *
     * @return float
     */
    public function classSkillXpGain(): float
    {
        return $this->amount(CharacterGemModifierType::CLASS_SKILL_XP_GAIN);
    }

    /**
     * Return the capped Class Mastery contribution multiplier bonus.
     *
     * @return float
     */
    public function classMasteryEffect(): float
    {
        return $this->amount(CharacterGemModifierType::CLASS_MASTERY_EFFECT);
    }

    /**
     * Return the capped Weapon Mastery contribution multiplier bonus.
     *
     * @return float
     */
    public function weaponMasteryEffect(): float
    {
        return $this->amount(CharacterGemModifierType::WEAPON_MASTERY_EFFECT);
    }

    /**
     * Return the capped Fire atonement.
     *
     * @return float
     */
    public function fireAtonement(): float
    {
        return $this->amount(CharacterGemModifierType::FIRE_ATONEMENT);
    }

    /**
     * Return the capped Water atonement.
     *
     * @return float
     */
    public function waterAtonement(): float
    {
        return $this->amount(CharacterGemModifierType::WATER_ATONEMENT);
    }

    /**
     * Return the capped Ice atonement.
     *
     * @return float
     */
    public function iceAtonement(): float
    {
        return $this->amount(CharacterGemModifierType::ICE_ATONEMENT);
    }

    /**
     * Return the capped Fire penetration.
     *
     * @return float
     */
    public function firePenetration(): float
    {
        return $this->amount(CharacterGemModifierType::FIRE_PENETRATION);
    }

    /**
     * Return the capped Water penetration.
     *
     * @return float
     */
    public function waterPenetration(): float
    {
        return $this->amount(CharacterGemModifierType::WATER_PENETRATION);
    }

    /**
     * Return the capped Ice penetration.
     *
     * @return float
     */
    public function icePenetration(): float
    {
        return $this->amount(CharacterGemModifierType::ICE_PENETRATION);
    }

    /**
     * Return the capped Gold gain.
     *
     * @return float
     */
    public function goldGain(): float
    {
        return $this->amount(CharacterGemModifierType::GOLD_GAIN);
    }

    /**
     * Return the capped Gold Dust gain.
     *
     * @return float
     */
    public function goldDustGain(): float
    {
        return $this->amount(CharacterGemModifierType::GOLD_DUST_GAIN);
    }

    /**
     * Return the capped Celestial Shard gain.
     *
     * @return float
     */
    public function shardsGain(): float
    {
        return $this->amount(CharacterGemModifierType::SHARDS_GAIN);
    }

    /**
     * Return the capped Copper Coin gain.
     *
     * @return float
     */
    public function copperCoinGain(): float
    {
        return $this->amount(CharacterGemModifierType::COPPER_COIN_GAIN);
    }

    /**
     * Return the capped Character XP gain.
     *
     * @return float
     */
    public function characterXpGain(): float
    {
        return $this->amount(CharacterGemModifierType::CHARACTER_XP_GAIN);
    }

    /**
     * Determine whether equipped Gems add one level to a normal level-up trigger.
     *
     * @return bool
     */
    public function gainsAdditionalLevel(): bool
    {
        return $this->amount(CharacterGemModifierType::ADDITIONAL_LEVEL_GAIN) > 0;
    }

    /**
     * Return cache-safe active Gem Ability snapshots.
     *
     * @return array
     */
    public function activeAbilitySnapshots(): array
    {
        return $this->activeAbilities;
    }

    /**
     * Return cache-safe passive Gem Ability snapshots.
     *
     * @return array
     */
    public function passiveAbilitySnapshots(): array
    {
        return $this->passiveAbilities;
    }

    /**
     * Return the capped passive Gem Ability bonus for one effect and attack action.
     *
     * @param GemAbilityEffectType $effectType
     * @param string $attackType
     * @return float
     */
    public function passiveBonusFor(GemAbilityEffectType $effectType, string $attackType): float
    {
        $bonus = array_sum(array_map(
            fn (array $ability): float => $ability['effect_type'] === $effectType->value
                && in_array($attackType, $ability['attack_types'], true)
                    ? $ability['effect_value']
                    : 0.0,
            $this->passiveAbilities,
        ));

        return min($bonus, 0.50);
    }

    /**
     * Return passive Gem Ability contribution rows for one combat effect type.
     *
     * @param string $effectType
     * @return array
     */
    public function passiveDetailsFor(string $effectType): array
    {
        return array_values(array_map(
            fn (array $ability): array => [
                'gem_id' => $ability['gem_id'],
                'gem_name' => $ability['gem_name'],
                'item_id' => $ability['item_id'],
                'item_name' => $ability['item_name'],
                'modifier_type' => CharacterGemModifierType::GEM_ABILITY->value,
                'amount' => $ability['effect_value'],
                'ability_name' => $ability['name'],
                'attack_types' => $ability['attack_types'],
            ],
            array_filter(
                $this->passiveAbilities,
                fn (array $ability): bool => $ability['effect_type'] === $effectType,
            ),
        ));
    }

    /**
     * Return factual per-Gem modifier contribution rows.
     *
     * @return array
     */
    public function gemDetails(): array
    {
        return $this->gemDetails;
    }

    /**
     * Return per-Gem detail rows for one modifier type.
     *
     * @param CharacterGemModifierType $modifierType
     * @return array
     */
    public function detailsFor(CharacterGemModifierType $modifierType): array
    {
        return array_values(array_filter(
            $this->gemDetails,
            fn (array $detail): bool => $detail['modifier_type'] === $modifierType->value,
        ));
    }

    /**
     * Serialize the resolved cache-safe Gem effect snapshot.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'amounts' => $this->amounts,
            'active_abilities' => $this->activeAbilities,
            'passive_abilities' => $this->passiveAbilities,
            'gem_details' => $this->gemDetails,
        ];
    }
}
