<?php

namespace App\Flare\Values;

use Exception;

class ItemHolyValue
{
    /**
     * @var string
     */
    private $value;

    const LEVEL_ONE = 1;

    const LEVEL_TWO = 2;

    const LEVEL_THREE = 3;

    const LEVEL_FOUR = 4;

    const LEVEL_FIVE = 5;

    /**
     * @var string[]
     */
    protected static $values = [
        self::LEVEL_ONE => 1,
        self::LEVEL_TWO => 2,
        self::LEVEL_THREE => 3,
        self::LEVEL_FOUR => 4,
        self::LEVEL_FIVE => 5,
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {

        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Gets random item stat increase.
     */
    public function getRandomStatIncrease(): int
    {
        if ($this->value === self::LEVEL_ONE) {
            return rand(1, 3);
        }

        if ($this->value === self::LEVEL_TWO) {
            return rand(1, 5);
        }

        if ($this->value === self::LEVEL_THREE) {
            return rand(1, 8);
        }

        if ($this->value === self::LEVEL_FOUR) {
            return rand(1, 10);
        }

        // Level 5
        return rand(1, 15);
    }

    /**
     * Gets random Devoidance increase based on holy level.
     */
    public function getRandomDevoidanceIncrease(): float
    {
        if ($this->value === self::LEVEL_ONE) {
            return rand(1, 3) / 1000;
        }

        if ($this->value === self::LEVEL_TWO) {
            return rand(1, 5) / 1000;
        }

        if ($this->value === self::LEVEL_THREE) {
            return rand(1, 8) / 1000;
        }

        if ($this->value === self::LEVEL_FOUR) {
            return rand(1, 10) / 1000;
        }

        // Level 5
        return rand(1, 15) / 1000;
    }
}
