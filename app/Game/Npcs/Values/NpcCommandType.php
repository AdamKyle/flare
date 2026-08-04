<?php

namespace App\Game\Npcs\Values;

enum NpcCommandType: int
{
    case QUEST = 0;
    case TAKE_KINGDOM = 1;
    case CONJURE = 2;
    case RE_ROLL = 3;

    public function label(): string
    {
        return match ($this) {
            self::QUEST => 'Quest', self::TAKE_KINGDOM => 'Take Kingdom', self::CONJURE => 'Conjure', self::RE_ROLL => 'Re-Roll'
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

    public function isQuest(): bool
    {
        return $this === self::QUEST;
    }

    public function isTakeKingdom(): bool
    {
        return $this === self::TAKE_KINGDOM;
    }

    public function isConjure(): bool
    {
        return $this === self::CONJURE;
    }

    public function isReRoll(): bool
    {
        return $this === self::RE_ROLL;
    }
}
