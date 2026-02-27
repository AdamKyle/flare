<?php

namespace App\Flare\Values;

class AutomationType
{
    /**
     * @var string
     */
    private $value;

    const EXPLORING = 0;

    const DELVE = 1;

    /**
     * @var int[]
     */
    protected static $values = [
        0 => self::EXPLORING,
        1 => self::DELVE,
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

    public function isDelve(): bool
    {
        return $this->value === self::DELVE;
    }
}
