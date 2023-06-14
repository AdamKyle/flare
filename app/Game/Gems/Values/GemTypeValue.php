<?php

namespace App\Game\Gems\Values;

use Illuminate\Support\Str;

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

    private static array $halfDamage = [
        self::FIRE  => self::WATER,
        self::ICE   => self::FIRE,
        self::WATER => self::ICE,
    ];

    private static array $doubleDamage = [
        self::WATER => self::FIRE,
        self::FIRE  => self::ICE,
        self::ICE  => self::WATER,
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

    public static function getOppsiteForHalfDamage(string $name): string {
        if (!in_array(Str::title($name), array_map('Str::title', array_values(self::$names)), true)) {
            throw new \Exception($name . ' does not exist.');
        }

        $value = array_search(strtolower($name), array_map('strtolower', self::$names));

        if ($value === false) {
            throw new \Exception($name . ' does not exist.');
        }

        $opposite = self::$halfDamage[$value];

        return self::$names[$opposite];
    }

    public static function getOppsiteForDoubleDamage(string $name): string {
        if (!in_array(Str::title($name), array_map('Str::title', array_values(self::$names)), true)) {
            throw new \Exception($name . ' does not exist.');
        }

        $value = array_search(strtolower($name), array_map('strtolower', self::$names));

        if ($value === false) {
            throw new \Exception($name . ' does not exist.');
        }

        $opposite = self::$doubleDamage[$value];

        return self::$names[$opposite];
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
