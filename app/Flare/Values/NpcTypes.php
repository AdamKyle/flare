<?php

namespace App\Flare\Values;

use Exception;

class NpcTypes
{
    /**
     * @var string
     */
    private $value;

    const KINGDOM_HOLDER = 0;

    const SUMMONER = 1;

    const QUEST_GIVER = 2;

    const SPECIAL_ENCHANTS = 3;

    /**
     * @var string[]
     */
    protected static $values = [
        self::KINGDOM_HOLDER => 0,
        self::SUMMONER => 1,
        self::QUEST_GIVER => 2,
        self::SPECIAL_ENCHANTS => 3,
    ];

    /**
     * @var string[]
     */
    protected static $namedValues = [
        0 => 'Kingdom Holder',
        1 => 'Summoner',
        2 => 'Quest Giver',
        3 => 'Special Enchantments',
    ];

    /**
     * KingdomLogStatusValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Get all the named values.
     *
     * @return string[]
     */
    public static function getNamedValues(): array
    {
        return self::$namedValues;
    }

    /**
     * See if the name exists in a named value.
     *
     * If it does return it, if not throw an exception.
     */
    public function getNamedValue(): string
    {
        return self::$namedValues[$this->value];
    }

    public function isKingdomHolder(): bool
    {
        return $this->value === self::KINGDOM_HOLDER;
    }

    public function isQuestHolder(): bool
    {
        return $this->value === self::QUEST_GIVER;
    }

    public function isConjurer(): bool
    {
        return $this->value === self::SUMMONER;
    }

    public function isEnchantress(): bool
    {
        return $this->value === self::SPECIAL_ENCHANTS;
    }
}
