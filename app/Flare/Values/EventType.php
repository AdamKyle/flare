<?php

namespace App\Flare\Values;

use Exception;

class EventType {


    const WEEKLY_CELESTIALS     = 0;

    const MONTHLY_PVP           = 1;

    const WEEKLY_CURRENCY_DROPS = 2;

    /**
     * @var string $value
     */
    private string $value;

    /**
     * @var int[] $values
     */
    protected static array $values = [
        0 => self::WEEKLY_CELESTIALS,
        1 => self::MONTHLY_PVP,
        2 => self::WEEKLY_CURRENCY_DROPS,
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

    /**
     * Is weekly celestials?
     *
     * @return bool
     */
    public function isWeeklyCelestials(): bool {
        return $this->value === self::WEEKLY_CELESTIALS;
    }

    /**
     * Is monthly pvp?
     *
     * @return bool
     */
    public function isMonthlyPVP(): bool {
        return $this->value === self::MONTHLY_PVP;
    }

    /**
     * Is weekly currency drops?
     *
     * @return bool
     */
    public function isWeeklyCurrencyDrops(): bool {
        return $this->value === self::WEEKLY_CURRENCY_DROPS;
    }
}
