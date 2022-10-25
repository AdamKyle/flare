<?php

namespace App\Flare\Values;

class LocationType {

    /**
     * @var string $value
     */
    private $value;

    const PURGATORY_SMITH_HOUSE = 0;
    const GOLD_MINES            = 1;
    const PURGATORY_DUNGEONS    = 2;

    protected static $values = [
        0 => self::PURGATORY_SMITH_HOUSE,
        1 => self::GOLD_MINES,
        2 => self::PURGATORY_DUNGEONS,
    ];

    /**
     * @var string[] $values
     */
    protected static $namedValues = [
        self::PURGATORY_SMITH_HOUSE => 'Purgatory Smiths House',
        self::GOLD_MINES            => 'Gold Mines',
        self::PURGATORY_DUNGEONS    => 'Purgatory Dungeons'
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws \Exception
     */
    public function __construct(int $value)
    {

        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
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
}
