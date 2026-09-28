<?php

namespace App\Game\Core\Combat\Values;

enum AttackType: string
{
    case ATTACK = 'attack';
    case VOIDED_ATTACK = 'voided_attack';
    case CAST = 'cast';
    case VOIDED_CAST = 'voided_cast';
    case CAST_AND_ATTACK = 'cast_and_attack';
    case VOIDED_CAST_AND_ATTACK = 'voided_cast_and_attack';
    case ATTACK_AND_CAST = 'attack_and_cast';
    case VOIDED_ATTACK_AND_CAST = 'voided_attack_and_cast';
    case DEFEND = 'defend';
    case VOIDED_DEFEND = 'voided_defend';

    /**
     * Determine whether the given value is a known attack type.
     *
     * @param string $value
     * @return bool
     */
    public static function attackTypeExists(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * Return the five non-voided top-level attack actions a player can select.
     *
     * @return array
     */
    public static function baseAttackTypes(): array
    {
        return [
            self::ATTACK,
            self::CAST,
            self::ATTACK_AND_CAST,
            self::CAST_AND_ATTACK,
            self::DEFEND,
        ];
    }

    /**
     * Return the non-voided top-level action this attack type belongs to.
     *
     * @return AttackType
     */
    public function baseAttackType(): AttackType
    {
        return match ($this) {
            self::ATTACK, self::VOIDED_ATTACK => self::ATTACK,
            self::CAST, self::VOIDED_CAST => self::CAST,
            self::CAST_AND_ATTACK, self::VOIDED_CAST_AND_ATTACK => self::CAST_AND_ATTACK,
            self::ATTACK_AND_CAST, self::VOIDED_ATTACK_AND_CAST => self::ATTACK_AND_CAST,
            self::DEFEND, self::VOIDED_DEFEND => self::DEFEND,
        };
    }

    /**
     * Determine whether this is the Attack action.
     *
     * @return bool
     */
    public function isAttack(): bool
    {
        return $this === self::ATTACK;
    }

    /**
     * Determine whether this is the voided Attack action.
     *
     * @return bool
     */
    public function isVoidedAttack(): bool
    {
        return $this === self::VOIDED_ATTACK;
    }

    /**
     * Determine whether this is the Cast action.
     *
     * @return bool
     */
    public function isCast(): bool
    {
        return $this === self::CAST;
    }

    /**
     * Determine whether this is the voided Cast action.
     *
     * @return bool
     */
    public function isVoidedCast(): bool
    {
        return $this === self::VOIDED_CAST;
    }

    /**
     * Determine whether this is the Attack and Cast action.
     *
     * @return bool
     */
    public function isAttackAndCast(): bool
    {
        return $this === self::ATTACK_AND_CAST;
    }

    /**
     * Determine whether this is the voided Attack and Cast action.
     *
     * @return bool
     */
    public function isVoidedAttackAndCast(): bool
    {
        return $this === self::VOIDED_ATTACK_AND_CAST;
    }

    /**
     * Determine whether this is the Cast and Attack action.
     *
     * @return bool
     */
    public function isCastAndAttack(): bool
    {
        return $this === self::CAST_AND_ATTACK;
    }

    /**
     * Determine whether this is the voided Cast and Attack action.
     *
     * @return bool
     */
    public function isVoidedCastAndAttack(): bool
    {
        return $this === self::VOIDED_CAST_AND_ATTACK;
    }

    /**
     * Determine whether this is the Defend action.
     *
     * @return bool
     */
    public function isDefend(): bool
    {
        return $this === self::DEFEND;
    }

    /**
     * Determine whether this is the voided Defend action.
     *
     * @return bool
     */
    public function isVoidedDefend(): bool
    {
        return $this === self::VOIDED_DEFEND;
    }
}
