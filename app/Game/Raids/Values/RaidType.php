<?php

namespace App\Game\Raids\Values;

use Exception;

class RaidType
{
    const PIRATE_LORD = 'pirate-lord';

    const ICE_QUEEN = 'ice-queen';

    const JESTER_OF_TIME = 'jester-of-time';

    const FROZEN_KING = 'frozen-king';

    const CORRUPTED_BISHOP = 'corrupted-bishop';

    const ENRAGED_LITTLE_GIRL = 'enraged-little-girl';

    private string $value;

    /**
     * @var string[]
     */
    protected static array $values = [
        self::PIRATE_LORD => self::PIRATE_LORD,
        self::ICE_QUEEN => self::ICE_QUEEN,
        self::JESTER_OF_TIME => self::JESTER_OF_TIME,
        self::FROZEN_KING => self::FROZEN_KING,
        self::CORRUPTED_BISHOP => self::CORRUPTED_BISHOP,
        self::ENRAGED_LITTLE_GIRL => self::ENRAGED_LITTLE_GIRL,
    ];

    public static array $selectionOptions = [
        self::PIRATE_LORD => 'Pirate Lord Raid',
        self::ICE_QUEEN => 'Ice Queen Raid',
        self::JESTER_OF_TIME => 'Jester of Time Raid',
        self::FROZEN_KING => 'Frozen King',
        self::CORRUPTED_BISHOP => 'Corrupted Bishop',
        self::ENRAGED_LITTLE_GIRL => 'Enraged Little Girl Raid',
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    public function getNameForRaid(): string
    {
        return self::$selectionOptions[$this->value];
    }

    public function isPirateLordRaid(): bool
    {
        return $this->value === self::PIRATE_LORD;
    }

    public function isIceQueenRaid(): bool
    {
        return $this->value === self::ICE_QUEEN;
    }

    public function isJesterOfTime(): bool
    {
        return $this->value === self::JESTER_OF_TIME;
    }

    public function isFrozenKing(): bool
    {
        return $this->value === self::FROZEN_KING;
    }

    public function isCorruptedBishop(): bool
    {
        return $this->value === self::CORRUPTED_BISHOP;
    }

    public function isEnragedLittleGirl(): bool
    {
        return $this->value === self::ENRAGED_LITTLE_GIRL;
    }
}
