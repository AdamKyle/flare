<?php

namespace App\Flare\Values;

class LocationType {

    /**
     * @var string $value
     */
    private $value;

    const PURGATORY_SMITH_HOUSE = 0;

    protected static $values = [
        0 => self::PURGATORY_SMITH_HOUSE,
    ];

    /**
     * @var string[] $values
     */
    protected static $namedValues = [
        self::PURGATORY_SMITH_HOUSE => 'Purgatory Smiths House',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws \Exception
     */
    public function __construct(int $value)
    {

        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public static function getNamedValues(): array {
        return self::$namedValues;
    }
}
