<?php

namespace App\Game\Automation\Values;

class AutomationType {

    /**
     * @var string $value
     */
    private $value;

    const ATTACK = 0;

    /**
     * @var int[] $values
     */
    protected static $values = [
        0 => self::ATTACK,
    ];

    /**
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

    /**
     * @return bool
     */
    public function isAttack(): bool {
        return $this->value === self::ATTACK;
    }

}
