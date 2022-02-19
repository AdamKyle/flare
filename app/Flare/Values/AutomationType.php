<?php

namespace App\Flare\Values;

class AutomationType {

    /**
     * @var string $value
     */
    private $value;

    const EXPLORING = 0;

    /**
     * @var int[] $values
     */
    protected static $values = [
        0 => self::EXPLORING,
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
    public function isExploring(): bool {
        return $this->value === self::EXPLORING;
    }

}
