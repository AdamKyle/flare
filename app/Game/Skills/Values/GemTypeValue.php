<?php

namespace App\Game\Skills\Values;

class GemTypeValue {

    const FIRE  = 0;
    const ICE   = 1;
    const WATER = 2;

    private int $value;

    private array $values = [
        self::FIRE  => self::FIRE,
        self::ICE   => self::ICE,
        self::WATER => self::WATER,
    ];

    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function isFire(): boolean {
        return $this->value === self::FIRE;
    }

    public function isIce(): boolean {
        return $this->value === self::ICE;
    }

    public function isWater(): boolean {
        return $this->value === self::WATER;
    }
}
