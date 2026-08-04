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

    public static function attackTypeExists(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    public function isAttack(): bool
    {
        return $this === self::ATTACK;
    }

    public function isVoidedAttack(): bool
    {
        return $this === self::VOIDED_ATTACK;
    }

    public function isCast(): bool
    {
        return $this === self::CAST;
    }

    public function isVoidedCast(): bool
    {
        return $this === self::VOIDED_CAST;
    }

    public function isAttackAndCast(): bool
    {
        return $this === self::ATTACK_AND_CAST;
    }

    public function isVoidedAttackAndCast(): bool
    {
        return $this === self::VOIDED_ATTACK_AND_CAST;
    }

    public function isCastAndAttack(): bool
    {
        return $this === self::CAST_AND_ATTACK;
    }

    public function isVoidedCastAndAttack(): bool
    {
        return $this === self::VOIDED_CAST_AND_ATTACK;
    }

    public function isDefend(): bool
    {
        return $this === self::DEFEND;
    }

    public function isVoidedDefend(): bool
    {
        return $this === self::VOIDED_DEFEND;
    }
}
