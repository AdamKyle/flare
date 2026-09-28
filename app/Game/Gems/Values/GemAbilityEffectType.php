<?php

namespace App\Game\Gems\Values;

enum GemAbilityEffectType: string
{
    case BONUS_DAMAGE = 'bonus_damage';
    case WEAPON_DAMAGE_MOD = 'weapon_damage_mod';
    case SPELL_DAMAGE_MOD = 'spell_damage_mod';
    case HEALING_MOD = 'healing_mod';
    case DEFENCE_MOD = 'defence_mod';

    /**
     * Return the effect types an active Gem Ability may use.
     *
     * @return array
     */
    public static function activeEffects(): array
    {
        return [self::BONUS_DAMAGE];
    }

    /**
     * Return the effect types a passive Gem Ability may use.
     *
     * @return array
     */
    public static function passiveEffects(): array
    {
        return [
            self::WEAPON_DAMAGE_MOD,
            self::SPELL_DAMAGE_MOD,
            self::HEALING_MOD,
            self::DEFENCE_MOD,
        ];
    }

    /**
     * Determine whether this effect type belongs to the given ability type.
     *
     * @param GemAbilityType $abilityType
     * @return bool
     */
    public function isAllowedFor(GemAbilityType $abilityType): bool
    {
        $allowedEffects = match ($abilityType) {
            GemAbilityType::ACTIVE => self::activeEffects(),
            GemAbilityType::PASSIVE => self::passiveEffects(),
        };

        return in_array($this, $allowedEffects, true);
    }
}
