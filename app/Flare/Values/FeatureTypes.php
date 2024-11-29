<?php

namespace App\Flare\Values;

use Exception;

class FeatureTypes
{
    const REINCARNATION = 0;

    const COSMETIC_TEXT = 1;

    const COSMETIC_NAME_TAGS = 2;

    const EXTEND_SETS = 3;

    const CAPITAL_CITIES = 4;

    const CAPITAL_CITY_GOLD_BARS = 5;

    const COSMETIC_RACE_CHANGER = 6;

    private int $value;

    /**
     * @var int[]
     */
    protected static array $values = [
        0 => self::REINCARNATION,
        1 => self::COSMETIC_TEXT,
        2 => self::COSMETIC_NAME_TAGS,
        3 => self::EXTEND_SETS,
        4 => self::CAPITAL_CITIES,
        5 => self::CAPITAL_CITY_GOLD_BARS,
        6 => self::COSMETIC_RACE_CHANGER,
    ];

    protected static array $valueNames = [
        self::REINCARNATION => 'Reincarnation',
        self::COSMETIC_TEXT => 'Cosmetic Text',
        self::COSMETIC_NAME_TAGS => 'Cosmetic Name Tags',
        self::EXTEND_SETS => 'Give 10 additional sets',
        self::CAPITAL_CITIES => 'Capital Cities',
        self::CAPITAL_CITY_GOLD_BARS => 'Capital City Gold Bars',
        self::COSMETIC_RACE_CHANGER => 'Cosmetic Race Changer',
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public static function getSelectable(): array
    {
        return self::$valueNames;
    }

    public function getNameOfFeature(): string
    {
        return self::$valueNames[$this->value];
    }

    public function isReincarnation(): bool
    {
        return $this->value === self::REINCARNATION;
    }

    public function isCosmeticText(): bool
    {
        return $this->value === self::COSMETIC_TEXT;
    }

    public function isCosmeticNameTag(): bool
    {
        return $this->value === self::COSMETIC_NAME_TAGS;
    }

    public function isExtendSets(): bool
    {
        return $this->value === self::EXTEND_SETS;
    }

    public function isCapitalCities(): bool
    {
        return $this->value === self::CAPITAL_CITIES;
    }

    public function isCosmeticRaceChanger(): bool
    {
        return $this->value === self::COSMETIC_RACE_CHANGER;
    }
}
