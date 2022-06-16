<?php

namespace App\Flare\Values;

use Exception;

class CelestialType {

    /**
     * @var string $value
     */
    private $value;

    const REGULAR_CELESTIAL = 0;

    const KING_CELESTIAL = 1;

    /**
     * @var int[] $values
     */
    protected static $values = [
        0 => self::REGULAR_CELESTIAL,
        1 => self::KING_CELESTIAL,
    ];

    /**
     * @var string[]
     */
    protected static $namedValues = [
        self::REGULAR_CELESTIAL => 'Regular Celestial',
        self::KING_CELESTIAL    => 'King Celestial',
    ];

    /**
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

    /**
     * @return bool
     */
    public function isRegularCelestial(): bool {
        return $this->value === self::REGULAR_CELESTIAL;
    }

    /**
     * @return bool
     */
    public function isKingCelestial(): bool {
        return $this->value === self::KING_CELESTIAL;
    }

    /**
     * @return string[]
     */
    public static function getNamedValues(): array {
        return self::$namedValues;
    }
}
