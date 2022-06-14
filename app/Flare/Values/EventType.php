<?php

namespace App\Flare\Values;

class EventType {

    /**
     * @var string $value
     */
    private string $value;

    const WEEKLY_CELESTIALS = 0;

    const MONTHLY_PVP = 1;

    /**
     * @var int[] $values
     */
    protected static array $values = [
        0 => self::WEEKLY_CELESTIALS,
        1 => self::MONTHLY_PVP,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
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
     * @return bool
     */
    public function isWeeklyCelestials(): bool {
        return $this->value === self::WEEKLY_CELESTIALS;
    }

    /**
     * @return bool
     */
    public function isMonthlyPVP(): bool {
        return $this->value === self::MONTHLY_PVP;
    }
}
