<?php

namespace App\Game\Npcs\Values;

enum NpcType: int
{
    case KINGDOM_HOLDER = 0;
    case SUMMONER = 1;
    case QUEST_GIVER = 2;
    case SPECIAL_ENCHANTS = 3;

    public function label(): string
    {
        return match ($this) {
            self::KINGDOM_HOLDER => 'Kingdom Holder', self::SUMMONER => 'Summoner', self::QUEST_GIVER => 'Quest Giver', self::SPECIAL_ENCHANTS => 'Special Enchantments'
        };
    }

    public static function getNamedValues(): array
    {
        return array_reduce(self::cases(), function (array $options, self $case): array {
            $options[$case->value] = $case->label();

            return $options;
        }, []);
    }

    public function getNamedValue(): string
    {
        return $this->label();
    }

    public function isKingdomHolder(): bool
    {
        return $this === self::KINGDOM_HOLDER;
    }

    public function isQuestHolder(): bool
    {
        return $this === self::QUEST_GIVER;
    }

    public function isConjurer(): bool
    {
        return $this === self::SUMMONER;
    }

    public function isEnchantress(): bool
    {
        return $this === self::SPECIAL_ENCHANTS;
    }
}
