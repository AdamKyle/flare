<?php

namespace App\Flare\Values;

use Exception;

class FeatureTypes {

    const REINCARNATION = 0;

    const COSMETIC_TEXT = 1;

    const NAME_TAGS = 2;

    const EXTEND_SETS = 3;

    const CAPITAL_CITIES = 4;

    const CAPITAL_CITY_GOLD_BARS = 5;

    /**
     * @var int $value
     */
    private int $value;

    /**
     * @var int[] $values
     */
    protected static array $values = [
        0 => self::REINCARNATION,
        1 => self::COSMETIC_TEXT,
        2 => self::NAME_TAGS,
        3 => self::EXTEND_SETS,
        4 => self::CAPITAL_CITIES,
        5 => self::CAPITAL_CITY_GOLD_BARS,
    ];

    protected static array $valueNames = [
        self::REINCARNATION => 'Reincarnation',
        self::COSMETIC_TEXT => 'Cosmetic Text',
        self::NAME_TAGS     => 'Name Tags',
        self::EXTEND_SETS   => 'Give 10 additional sets',
        self::CAPITAL_CITIES => 'Capital Cities',
        self::CAPITAL_CITY_GOLD_BARS => 'Capital City Gold Bars',
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public static function getSelectable(): array {
        return self::$valueNames;
    }

    public function getNameOfFeature(): string {
        return self::$valueNames[$this->value];
    }

    public function isReincarnation(): bool {
        return $this->value === self::REINCARNATION;
    }

    public function isCosmeticText(): bool {
        return $this->value === self::COSMETIC_TEXT;
    }

    public function isNameTag(): bool {
        return $this->value === self::NAME_TAGS;
    }

    public function isExtendSets(): bool {
        return $this->value === self::EXTEND_SETS;
    }

    public function isCapitalCities(): bool {
        return $this->value === self::CAPITAL_CITIES;
    }
}
