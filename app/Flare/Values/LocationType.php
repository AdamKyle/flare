<?php

namespace App\Flare\Values;

use Exception;

class LocationType
{
    /**
     * @var string
     */
    private $value;

    const PURGATORY_SMITH_HOUSE = 0;

    const GOLD_MINES = 1;

    const PURGATORY_DUNGEONS = 2;

    const UNDERWATER_CAVES = 3;

    const TEAR_FABRIC_TIME = 4;

    const THE_OLD_CHURCH = 5;

    const TWISTED_GATE = 6;

    const ALCHEMY_CHURCH = 7;

    const LORDS_STRONG_HOLD = 8;

    const BROKEN_ANVIL = 9;

    const TWSITED_MAIDENS_DUNGEONS = 10;

    const CAVE_OF_MEMORIES = 11;

    const THE_CELLAR = 12;

    protected static $values = [
        0 => self::PURGATORY_SMITH_HOUSE,
        1 => self::GOLD_MINES,
        2 => self::PURGATORY_DUNGEONS,
        3 => self::UNDERWATER_CAVES,
        4 => self::TEAR_FABRIC_TIME,
        5 => self::THE_OLD_CHURCH,
        6 => self::TWISTED_GATE,
        7 => self::ALCHEMY_CHURCH,
        8 => self::LORDS_STRONG_HOLD,
        9 => self::BROKEN_ANVIL,
        10 => self::TWSITED_MAIDENS_DUNGEONS,
        11 => self::CAVE_OF_MEMORIES,
        12 => self::THE_CELLAR,
    ];

    /**
     * @var string[]
     */
    protected static $namedValues = [
        self::PURGATORY_SMITH_HOUSE => 'Purgatory Smiths House',
        self::GOLD_MINES => 'Gold Mines',
        self::PURGATORY_DUNGEONS => 'Purgatory Dungeons',
        self::UNDERWATER_CAVES => 'Underwater Caves',
        self::TEAR_FABRIC_TIME => 'Tear in the fabrice of time',
        self::THE_OLD_CHURCH => 'The Old Church',
        self::TWISTED_GATE => 'The Twisted Gate',
        self::ALCHEMY_CHURCH => 'Alchemy Church',
        self::LORDS_STRONG_HOLD => 'Lords Strong Hold',
        self::BROKEN_ANVIL => 'Hells Broken Anvil',
        self::TWSITED_MAIDENS_DUNGEONS => 'Twisted Maidens Dungeons',
        self::CAVE_OF_MEMORIES => 'Cave of Memories',
        self::THE_CELLAR => 'The Cellar',
    ];

    /**
     * ItemEffectsValue constructor.
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

    public static function getNamedValues(): array
    {
        return self::$namedValues;
    }

    public function getNamedValue(): string
    {
        return self::$namedValues[$this->value];
    }

    /**
     * Is purgatory smith house?
     */
    public function isPurgatorySmithHouse(): bool
    {
        return $this->value === self::PURGATORY_SMITH_HOUSE;
    }

    /**
     * Is gold mines?
     */
    public function isGoldMines(): bool
    {
        return $this->value === self::GOLD_MINES;
    }

    /**
     * Is Purgatory dungeons?
     */
    public function isPurgatoryDungeons(): bool
    {
        return $this->value === self::PURGATORY_DUNGEONS;
    }

    /**
     * Is underwater caves?
     */
    public function isUnderWaterCaves(): bool
    {
        return $this->value === self::UNDERWATER_CAVES;
    }

    /**
     * is the old church?
     */
    public function isTheOldChurch(): bool
    {
        return $this->value === self::THE_OLD_CHURCH;
    }

    /**
     * Is the twisted gate?
     */
    public function isTwistedGate(): bool
    {
        return $this->value === self::TWISTED_GATE;
    }

    public function isTheCellar(): bool {
        return $this->value === self::THE_CELLAR;
    }

    /**
     * Are we at the alchemy church?
     */
    public function isAlchemyChurch(): bool
    {
        return $this->value === self::ALCHEMY_CHURCH;
    }

    public function isLordsStrongHold(): bool
    {
        return $this->value === self::LORDS_STRONG_HOLD;
    }

    public function isHellsBrokenAnvil(): bool
    {
        return $this->value === self::BROKEN_ANVIL;
    }

    public function isTwistedMaidensDungeons(): bool
    {
        return $this->value === self::TWSITED_MAIDENS_DUNGEONS;
    }

    public function isCaveOfMemories(): bool
    {
        return $this->value === self::CAVE_OF_MEMORIES;
    }
}
