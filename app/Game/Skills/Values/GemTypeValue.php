<?php

namespace App\Game\Skills\Values;

class GemTypeValue {

    const FIRE  = 0;
    const ICE   = 1;
    const WATER = 2;

    private int $value;

    private static array $values = [
        self::FIRE  => self::FIRE,
        self::ICE   => self::ICE,
        self::WATER => self::WATER,
    ];

    private array $names = [
        self::FIRE  => 'Fire',
        self::ICE   => 'Ice',
        self::WATER => 'Water',
    ];

    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public function getNameOfAtonement(): string {
        return $this->names[$this->value];
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
