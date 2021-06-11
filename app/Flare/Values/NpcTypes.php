<?php

namespace App\Flare\Values;

class NpcTypes
{

    /**
     * @var string $value
     */
    private $value;

    const KINGDOM_HOLDER = 0;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::KINGDOM_HOLDER => 0,
    ];

    /**
     * KingdomLogStatusValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(string $value)
    {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }
}
