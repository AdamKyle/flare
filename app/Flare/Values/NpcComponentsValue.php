<?php

namespace App\Flare\Values;

use Exception;

class NpcComponentsValue
{

    /**
     * @var string $value
     */
    private $value;

    const CONJURE = 'Conjure';

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::CONJURE => 'Conjure',
    ];

    /**
     * KingdomLogStatusValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }
}
