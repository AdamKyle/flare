<?php

namespace App\Game\Core\Gems\Values;

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

    private static array $names = [
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

    public static function getNames(): array {
        return self::$names;
    }

    public function getNameOfAtonement(): string {
        return self::$names[$this->value];
    }

    public function isFire(): bool {
        return $this->value === self::FIRE;
    }

    public function isIce(): bool {
        return $this->value === self::ICE;
    }

    public function isWater(): bool {
        return $this->value === self::WATER;
    }
}
