<?php

namespace App\Flare\Values;

use Exception;

class LocationType {

    /**
     * @var string $value
     */
    private $value;

    const PURGATORY_SMITH_HOUSE = 0;
    const GOLD_MINES            = 1;
    const PURGATORY_DUNGEONS    = 2;
    const UNDERWATER_CAVES      = 3;
    const TEAR_FABRIC_TIME      = 4;
    const THE_OLD_CHURCH        = 5;
    const TWISTED_GATE          = 6;
    const ALCHEMY_CHURCH        = 7;

    protected static $values = [
        0 => self::PURGATORY_SMITH_HOUSE,
        1 => self::GOLD_MINES,
        2 => self::PURGATORY_DUNGEONS,
        3 => self::UNDERWATER_CAVES,
        4 => self::TEAR_FABRIC_TIME,
        5 => self::THE_OLD_CHURCH,
        6 => self::TWISTED_GATE,
        7 => self::ALCHEMY_CHURCH,
    ];

    /**
     * @var string[] $values
     */
    protected static $namedValues = [
        self::PURGATORY_SMITH_HOUSE => 'Purgatory Smiths House',
        self::GOLD_MINES            => 'Gold Mines',
        self::PURGATORY_DUNGEONS    => 'Purgatory Dungeons',
        self::UNDERWATER_CAVES      => 'Underwater Caves',
        self::TEAR_FABRIC_TIME      => 'Tear in the fabrice of time',
        self::THE_OLD_CHURCH        => 'The Old Church',
        self::TWISTED_GATE          => 'The Twisted Gate',
        self::ALCHEMY_CHURCH        => 'Alchemy Church',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value)
    {

        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public static function getNamedValues(): array {
        return self::$namedValues;
    }

    /**
     * Is purgatory smith house?
     *
     * @return bool
     */
    public function isPurgatorySmithHouse(): bool {
        return $this->value === self::PURGATORY_SMITH_HOUSE;
    }

    /**
     * Is gold mines?
     *
     * @return bool
     */
    public function isGoldMines(): bool {
        return $this->value === self::GOLD_MINES;
    }

    /**
     * Is Purgatory dungeons?
     *
     * @return bool
     */
    public function isPurgatoryDungeons(): bool {
        return $this->value === self::PURGATORY_DUNGEONS;
    }

    /**
     * Is underwater caves?
     *
     * @return bool
     */
    public function isUnderWaterCaves(): bool {
        return $this->value === self::UNDERWATER_CAVES;
    }

    /**
     * is the old church?
     *
     * @return bool
     */
    public function isTheOldChurch(): bool {
        return $this->value === self::THE_OLD_CHURCH;
    }

    /**
     * Is the twisted gate?
     *
     * @return bool
     */
    public function isTwistedGate(): bool {
        return $this->value === self::TWISTED_GATE;
    }

    /**
     * Are we at the alchemy church?
     *
     * @return bool
     */
    public function isAlchemyChurch(): bool {
        return $this->value === self::ALCHEMY_CHURCH;
    }
}
