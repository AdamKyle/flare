<?php

namespace App\Game\Raids\Values;

enum RaidAttackType: int
{
    case PHYSICAL_ATTACK = 0;
    case MAGICAL_ICE_ATTACK = 1;
    case DELUSIONAL_MEMORIES_ATTACK = 2;
    case BANSHEE_SCREAM_ATTACK = 3;
    case ENRAGED_HATE = 4;

    public function label(): string
    {
        return match ($this) {
            self::PHYSICAL_ATTACK => 'Physical Attack', self::MAGICAL_ICE_ATTACK => 'Magical Ice Attack', self::DELUSIONAL_MEMORIES_ATTACK => 'Delusional Memories Attack', self::BANSHEE_SCREAM_ATTACK => 'Banshee Scream Attack', self::ENRAGED_HATE => 'Enraged Hate'
        };
    }

    public static function attackTypeNames(): array
    {
        return array_reduce(self::cases(), function (array $names, self $case): array {
            $names[$case->value] = $case->label();

            return $names;
        }, []);
    }

    public function isPhysicalAttack(): bool
    {
        return $this === self::PHYSICAL_ATTACK;
    }

    public function isMagicalIceAttack(): bool
    {
        return $this === self::MAGICAL_ICE_ATTACK;
    }

    public function isDelusionalMemoriesAttack(): bool
    {
        return $this === self::DELUSIONAL_MEMORIES_ATTACK;
    }

    public function isBansheeScreamAttack(): bool
    {
        return $this === self::BANSHEE_SCREAM_ATTACK;
    }

    public function isEnragedHate(): bool
    {
        return $this === self::ENRAGED_HATE;
    }
}
