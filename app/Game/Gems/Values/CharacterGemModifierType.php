<?php

namespace App\Game\Gems\Values;

enum CharacterGemModifierType: string
{
    case GEM_ABILITY = 'gem_ability';
    case STRENGTH = 'strength';
    case DEXTERITY = 'dexterity';
    case INTELLIGENCE = 'intelligence';
    case DURABILITY = 'durability';
    case CHARISMA = 'charisma';
    case AGILITY = 'agility';
    case FOCUS = 'focus';
    case BASE_DAMAGE_MOD = 'base_damage_mod';
    case BASE_AC_MOD = 'base_ac_mod';
    case BASE_HEALING_MOD = 'base_healing_mod';
    case BASE_SPELL_DAMAGE_MOD = 'base_spell_damage_mod';
    case CLASS_RANK_XP_GAIN = 'class_rank_xp_gain';
    case CLASS_MASTERY_XP_GAIN = 'class_mastery_xp_gain';
    case WEAPON_MASTERY_XP_GAIN = 'weapon_mastery_xp_gain';
    case CLASS_SKILL_XP_GAIN = 'class_skill_xp_gain';
    case CLASS_MASTERY_EFFECT = 'class_mastery_effect';
    case WEAPON_MASTERY_EFFECT = 'weapon_mastery_effect';
    case FIRE_ATONEMENT = 'fire_atonement';
    case WATER_ATONEMENT = 'water_atonement';
    case ICE_ATONEMENT = 'ice_atonement';
    case FIRE_PENETRATION = 'fire_penetration';
    case WATER_PENETRATION = 'water_penetration';
    case ICE_PENETRATION = 'ice_penetration';
    case GOLD_GAIN = 'gold_gain';
    case GOLD_DUST_GAIN = 'gold_dust_gain';
    case SHARDS_GAIN = 'shards_gain';
    case COPPER_COIN_GAIN = 'copper_coin_gain';
    case CHARACTER_XP_GAIN = 'character_xp_gain';
    case ADDITIONAL_LEVEL_GAIN = 'additional_level_gain';

    /**
     * Return the seven raw stat modifier types.
     *
     * @return array
     */
    public static function rawStats(): array
    {
        return [
            self::STRENGTH,
            self::DEXTERITY,
            self::INTELLIGENCE,
            self::DURABILITY,
            self::CHARISMA,
            self::AGILITY,
            self::FOCUS,
        ];
    }

    /**
     * Return the Tier 2 direct combat modifier types.
     *
     * @return array
     */
    public static function combatModifiers(): array
    {
        return [
            self::BASE_DAMAGE_MOD,
            self::BASE_AC_MOD,
            self::BASE_HEALING_MOD,
            self::BASE_SPELL_DAMAGE_MOD,
        ];
    }

    /**
     * Return the Tier 3 progression XP gain modifier types.
     *
     * @return array
     */
    public static function progressionXpGains(): array
    {
        return [
            self::CLASS_RANK_XP_GAIN,
            self::CLASS_MASTERY_XP_GAIN,
            self::WEAPON_MASTERY_XP_GAIN,
            self::CLASS_SKILL_XP_GAIN,
        ];
    }

    /**
     * Return the Tier 3 mastery effect modifier types.
     *
     * @return array
     */
    public static function masteryEffects(): array
    {
        return [
            self::CLASS_MASTERY_EFFECT,
            self::WEAPON_MASTERY_EFFECT,
        ];
    }

    /**
     * Return the Tier 4 elemental atonement modifier types.
     *
     * @return array
     */
    public static function atonements(): array
    {
        return [
            self::FIRE_ATONEMENT,
            self::WATER_ATONEMENT,
            self::ICE_ATONEMENT,
        ];
    }

    /**
     * Return the Tier 4 elemental penetration modifier types.
     *
     * @return array
     */
    public static function penetrations(): array
    {
        return [
            self::FIRE_PENETRATION,
            self::WATER_PENETRATION,
            self::ICE_PENETRATION,
        ];
    }

    /**
     * Return the Tier 4 currency gain modifier types.
     *
     * @return array
     */
    public static function currencyGains(): array
    {
        return [
            self::GOLD_GAIN,
            self::GOLD_DUST_GAIN,
            self::SHARDS_GAIN,
            self::COPPER_COIN_GAIN,
        ];
    }

    /**
     * Return every modifier type that a Gem of the given tier may roll.
     *
     * @param int $tier
     * @return array
     */
    public static function forTier(int $tier): array
    {
        return match ($tier) {
            GemTierValue::TIER_ONE => [self::GEM_ABILITY, ...self::rawStats()],
            GemTierValue::TIER_TWO => [...self::rawStats(), ...self::combatModifiers()],
            GemTierValue::TIER_THREE => [...self::progressionXpGains(), ...self::masteryEffects()],
            GemTierValue::TIER_FOUR => [
                ...self::atonements(),
                ...self::penetrations(),
                ...self::currencyGains(),
                self::CHARACTER_XP_GAIN,
                self::ADDITIONAL_LEVEL_GAIN,
            ],
            default => [],
        };
    }

    /**
     * Determine whether a Gem of the given tier may roll this modifier type.
     *
     * @param int $tier
     * @return bool
     */
    public function belongsToTier(int $tier): bool
    {
        return in_array($this, self::forTier($tier), true);
    }

    /**
     * Determine whether this modifier type is one of the seven raw stats.
     *
     * @return bool
     */
    public function isRawStat(): bool
    {
        return in_array($this, self::rawStats(), true);
    }

    /**
     * Return the persisted Character stat attribute this raw stat modifier adds to.
     *
     * @return string|null
     */
    public function statKey(): ?string
    {
        return match ($this) {
            self::STRENGTH => 'str',
            self::DEXTERITY => 'dex',
            self::INTELLIGENCE => 'int',
            self::DURABILITY => 'dur',
            self::CHARISMA => 'chr',
            self::AGILITY => 'agi',
            self::FOCUS => 'focus',
            default => null,
        };
    }

    /**
     * Return the raw stat modifier type for the given persisted Character stat attribute.
     *
     * @param string $statKey
     * @return CharacterGemModifierType|null
     */
    public static function fromStatKey(string $statKey): ?CharacterGemModifierType
    {
        return match ($statKey) {
            'str' => self::STRENGTH,
            'dex' => self::DEXTERITY,
            'int' => self::INTELLIGENCE,
            'dur' => self::DURABILITY,
            'chr' => self::CHARISMA,
            'agi' => self::AGILITY,
            'focus' => self::FOCUS,
            default => null,
        };
    }
}
