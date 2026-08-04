<?php

namespace App\Game\Core\Values;

enum FeatureType: int
{
    case REINCARNATION = 0;
    case COSMETIC_TEXT = 1;
    case COSMETIC_NAME_TAGS = 2;
    case EXTEND_SETS = 3;
    case CAPITAL_CITIES = 4;
    case CAPITAL_CITY_GOLD_BARS = 5;
    case COSMETIC_RACE_CHANGER = 6;
    case EXTENDED_BACKPACK = 7;

    public function label(): string
    {
        return match ($this) {
            self::REINCARNATION => 'Reincarnation',
            self::COSMETIC_TEXT => 'Cosmetic Text',
            self::COSMETIC_NAME_TAGS => 'Cosmetic Name Tags',
            self::EXTEND_SETS => 'Give 10 additional sets',
            self::CAPITAL_CITIES => 'Capital Cities',
            self::CAPITAL_CITY_GOLD_BARS => 'Capital City Gold Bars',
            self::COSMETIC_RACE_CHANGER => 'Cosmetic Race Changer',
            self::EXTENDED_BACKPACK => 'Increased Inventory Space (150 slots)',
        };
    }

    public static function getSelectable(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public function getNameOfFeature(): string
    {
        return $this->label();
    }

    public function isReincarnation(): bool
    {
        return $this === self::REINCARNATION;
    }

    public function isCosmeticText(): bool
    {
        return $this === self::COSMETIC_TEXT;
    }

    public function isCosmeticNameTag(): bool
    {
        return $this === self::COSMETIC_NAME_TAGS;
    }

    public function isExtendSets(): bool
    {
        return $this === self::EXTEND_SETS;
    }

    public function isExtendedBackpack(): bool
    {
        return $this === self::EXTENDED_BACKPACK;
    }

    public function isCapitalCities(): bool
    {
        return $this === self::CAPITAL_CITIES;
    }

    public function isCapitalCityGoldBars(): bool
    {
        return $this === self::CAPITAL_CITY_GOLD_BARS;
    }

    public function isCosmeticRaceChanger(): bool
    {
        return $this === self::COSMETIC_RACE_CHANGER;
    }
}
