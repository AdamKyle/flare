<?php

namespace App\Flare\Values;

use Exception;

class RaidAttackTypesValue {

    private int $value;

    const PHYSICAL_ATTACK = 0;

    protected static array $values = [
        self::PHYSICAL_ATTACK => self::PHYSICAL_ATTACK
    ];

    public static $attackTypeNames = [
        self::PHYSICAL_ATTACK => 'Physical Attack',
    ];

    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function isPhysicalAttack(): bool {
        return $this->value === self::PHYSICAL_ATTACK;
    }
}
