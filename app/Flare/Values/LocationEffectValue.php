<?php

namespace App\Flare\Values;

class LocationEffectValue {

    /**
     * @var string $value
     */
    private $value;

    const INCREASE_STATS_BY_HUNDRED_THOUSAND = 0;
    const INCREASE_STATS_BY_ONE_MILLION      = 1;
    const INCREASE_STATS_BY_TEN_MILLION      = 2;
    const INCREASE_STATS_BY_HUNDRED_MILLION  = 3;
    const INCREASE_STATS_BY_ONE_BILLION      = 4;

    protected static $values = [
        0 => self::INCREASE_STATS_BY_HUNDRED_THOUSAND,
        1 => self::INCREASE_STATS_BY_ONE_MILLION,
        2 => self::INCREASE_STATS_BY_TEN_MILLION,
        3 => self::INCREASE_STATS_BY_HUNDRED_MILLION,
        4 => self::INCREASE_STATS_BY_ONE_BILLION,
    ];

    /**
     * @var string[] $values
     */
    protected static $namedValues = [
        self::INCREASE_STATS_BY_HUNDRED_THOUSAND => 'Increases monster stats by hundred thousand',
        self::INCREASE_STATS_BY_ONE_MILLION      => 'Increases monster stats by one million',
        self::INCREASE_STATS_BY_TEN_MILLION      => 'Increases monster stats by ten million',
        self::INCREASE_STATS_BY_HUNDRED_MILLION  => 'Increases monster stats by hundred million',
        self::INCREASE_STATS_BY_ONE_BILLION      => 'Increases monster stats by one billion',
    ];

    protected static $integerValues = [
        self::INCREASE_STATS_BY_HUNDRED_THOUSAND => 100000,
        self::INCREASE_STATS_BY_ONE_MILLION      => 1000000,
        self::INCREASE_STATS_BY_TEN_MILLION      => 10000000,
        self::INCREASE_STATS_BY_HUNDRED_MILLION  => 100000000,
        self::INCREASE_STATS_BY_ONE_BILLION      => 1000000000,
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

    /**
     * Do we increase by 100 thousand?
     *
     * @return bool
     */
    public function increasesByOneHundredThousand(): bool {
        return $this->value === self::INCREASE_STATS_BY_HUNDRED_THOUSAND;
    }

    /**
     * Do we increase by 1 million?
     *
     * @return bool
     */
    public function increaseByOneMillion(): bool {
        return $this->value === self::INCREASE_STATS_BY_ONE_MILLION;
    }

    /**
     * Do we increase by 10 million?
     *
     * @return bool
     */
    public function increaseByTenMillion(): bool {
        return $this->value === self::INCREASE_STATS_BY_TEN_MILLION;
    }

    /**
     * Do we increase by 100 million?
     *
     * @return bool
     */
    public function increaseByHundredMillion(): bool {
        return $this->value === self::INCREASE_STATS_BY_HUNDRED_MILLION;
    }

    /**
     * Do we increase by 1 billion?
     *
     * @return bool
     */
    public function increaseByOneBillion(): bool {
        return $this->value === self::INCREASE_STATS_BY_ONE_BILLION;
    }

    /**
     * Fetch the drop rate for a location based on monster strength.
     *
     * @return float
     */
    public function fetchDropRate(): float {
        switch ($this->value) {
            case self::INCREASE_STATS_BY_HUNDRED_THOUSAND:
                return 0.02;
            case self::INCREASE_STATS_BY_ONE_MILLION:
                return 0.05;
            case self::INCREASE_STATS_BY_TEN_MILLION:
                return 0.08;
            case self::INCREASE_STATS_BY_HUNDRED_MILLION:
                return 0.10;
            case self::INCREASE_STATS_BY_ONE_BILLION:
                return 0.14;
            default:
                // @codeCoverageIgnoreStart
                return 0.0;
                // @codeCoverageIgnoreEnd
        }
    }

    public static function fetchPercentageIncrease($value): float {
        switch ($value) {
            case self::INCREASE_STATS_BY_HUNDRED_THOUSAND:
                return 0.05;
            case self::INCREASE_STATS_BY_ONE_MILLION:
                return 0.10;
            case self::INCREASE_STATS_BY_TEN_MILLION:
                return 0.25;
            case self::INCREASE_STATS_BY_HUNDRED_MILLION:
                return 0.50;
            case self::INCREASE_STATS_BY_ONE_BILLION:
                return 0.70;
            default:
                return 0.0;
        }
    }

    /**
     * Get the named value.
     *
     * @return string
     */
    public static function getNamedValues(): array {
        return self::$namedValues;
    }

    public static function getIncreaseName($value): string {
        return self::$namedValues[$value];
    }

    public static function getIncreaseByAmount($value): int {
        return self::$integerValues[$value];
    }
}
