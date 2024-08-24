<?php

namespace App\Flare\Values;

class AutomationType
{
    /**
     * @var string
     */
    private $value;

    const EXPLORING = 0;

    const PVP_MONTHLY = 1;

    /**
     * @var int[]
     */
    protected static $values = [
        0 => self::EXPLORING,
        1 => self::PVP_MONTHLY,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @param  string  $value
     *
     * @throws \Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new \Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    public function isExploring(): bool
    {
        return $this->value === self::EXPLORING;
    }

    public function isInPvpMonthly(): bool
    {
        return $this->value === self::PVP_MONTHLY;
    }
}
