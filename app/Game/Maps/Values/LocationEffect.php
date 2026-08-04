<?php

namespace App\Game\Maps\Values;

enum LocationEffect: int
{
    case INCREASE_STATS_BY_TWO_HUNDRED_FIFTY = 0;
    case INCREASE_STATS_BY_FIVE_HUNDRED = 1;
    case INCREASE_STATS_BY_ONE_THOUSAND = 2;
    case INCREASE_STATS_BY_TWO_THOUSAND = 3;
    case INCREASE_STATS_BY_THREE_THOUSAND = 4;
    case INCREASE_STATS_BY_TEN_THOUSAND = 5;
    case INCREASE_STATS_BY_FIFTY_THOUSAND = 6;

    public function fetchDropRate(): float
    {
        return match ($this) {
            self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY => 0.02,
            self::INCREASE_STATS_BY_FIVE_HUNDRED => 0.05,
            self::INCREASE_STATS_BY_ONE_THOUSAND => 0.08,
            self::INCREASE_STATS_BY_TWO_THOUSAND => 0.10,
            self::INCREASE_STATS_BY_THREE_THOUSAND => 0.14,
            self::INCREASE_STATS_BY_TEN_THOUSAND => 0.30,
            self::INCREASE_STATS_BY_FIFTY_THOUSAND => 0.60,
        };
    }

    public static function fetchPercentageIncrease(int $value): float
    {
        return self::tryFrom($value)?->fetchDropRate() ?? 0.0;
    }

    public static function getNamedValues(): array
    {
        return [
            self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY->value => '250pts and 2% towards resistances and skills.',
            self::INCREASE_STATS_BY_FIVE_HUNDRED->value => '500pts and 5% towards resistances and skills.',
            self::INCREASE_STATS_BY_ONE_THOUSAND->value => '1,000pts and 8% towards resistances and skills. ',
            self::INCREASE_STATS_BY_TWO_THOUSAND->value => '2,000pts and 10% towards resistances and skills.',
            self::INCREASE_STATS_BY_THREE_THOUSAND->value => '3,000pts and 14% towards resistances and skills.',
            self::INCREASE_STATS_BY_TEN_THOUSAND->value => '10,000pts and 30% towards resistances and skills.',
            self::INCREASE_STATS_BY_FIFTY_THOUSAND->value => '50,000pts and 60% towards resistances and skills.',
        ];
    }

    public static function getIncreaseName(int $value): string
    {
        return self::getNamedValues()[$value];
    }

    public static function getIncreaseByAmount(int $value): int
    {
        return match (self::from($value)) {
            self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY => 250,
            self::INCREASE_STATS_BY_FIVE_HUNDRED => 500,
            self::INCREASE_STATS_BY_ONE_THOUSAND => 1000,
            self::INCREASE_STATS_BY_TWO_THOUSAND => 2000,
            self::INCREASE_STATS_BY_THREE_THOUSAND => 3000,
            self::INCREASE_STATS_BY_TEN_THOUSAND => 10000,
            self::INCREASE_STATS_BY_FIFTY_THOUSAND => 50000,
        };
    }
}
