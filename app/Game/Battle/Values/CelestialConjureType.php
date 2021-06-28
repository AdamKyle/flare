<?php

namespace App\Game\Battle\Values;

class CelestialConjureType {

    /**
     * @var string $value
     */
    private $value;

    const PUBLIC  = 0;
    const PRIVATE = 1;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::PUBLIC  => 0,
        self::PRIVATE => 1,
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Is public conjuration
     *
     * @return bool
     */
    public function isPublic(): bool {
        return $this->value === self::PUBLIC;
    }

    /**
     * Is private conjuration
     *
     * @return bool
     */
    public function isPrivate(): bool {
        return $this->value === self::PRIVATE;
    }
}
