<?php

namespace App\Flare\Values;

use Exception;

class RaidAttackTypesValue
{
    private int $value;

    const PHYSICAL_ATTACK = 0;

    const MAGICAL_ICE_ATTACK = 1;

    const DELUSIONAL_MEMORIES_ATTACK = 2;

    const BANSHEE_SCREAM_ATTACK = 3;

    const ENRAGED_HATE = 4;

    protected static array $values = [
        self::PHYSICAL_ATTACK => self::PHYSICAL_ATTACK,
        self::MAGICAL_ICE_ATTACK => self::MAGICAL_ICE_ATTACK,
        self::DELUSIONAL_MEMORIES_ATTACK => self::DELUSIONAL_MEMORIES_ATTACK,
        self::BANSHEE_SCREAM_ATTACK => self::BANSHEE_SCREAM_ATTACK,
        self::ENRAGED_HATE => self::ENRAGED_HATE,
    ];

    public static $attackTypeNames = [
        self::PHYSICAL_ATTACK => 'Physical Attack',
        self::MAGICAL_ICE_ATTACK => 'Magical Ice Attack',
        self::DELUSIONAL_MEMORIES_ATTACK => 'Delusional Memories Attack',
        self::BANSHEE_SCREAM_ATTACK => 'Banshee Scream Attack',
        self::ENRAGED_HATE => 'Enraged Hate',
    ];

    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    public function isPhysicalAttack(): bool
    {
        return $this->value === self::PHYSICAL_ATTACK;
    }

    public function isMagicalIceAttack(): bool
    {
        return $this->value === self::MAGICAL_ICE_ATTACK;
    }

    public function isDelusionalMemoriesAttack(): bool
    {
        return $this->value === self::DELUSIONAL_MEMORIES_ATTACK;
    }

    public function isBansheeScreamAttack(): bool
    {
        return $this->value === self::BANSHEE_SCREAM_ATTACK;
    }

    public function isEnragedHate(): bool
    {
        return $this->value === self::ENRAGED_HATE;
    }
}
