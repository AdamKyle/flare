<?php

namespace App\Flare\Values;

use Exception;

class AutomationType
{
    /**
     * @var string
     */
    private $value;

    const int EXPLORING = 0;
    const int DELVE = 1;
    const int FACTION_LOYALTY = 2;

    /**
     * @var int[]
     */
    protected static $values = [
        0 => self::EXPLORING,
        1 => self::DELVE,
        2 => self::FACTION_LOYALTY,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
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

    public function isExploring(): bool
    {
        return $this->value === self::EXPLORING;
    }

    public function isDelve(): bool
    {
        return $this->value === self::DELVE;
    }

    public function isFactionLoyalty(): bool
    {
        return $this->value === self::FACTION_LOYALTY;
    }
}
