<?php

namespace App\Flare\Values;

class ItemEffectsValue {

    /**
     * @var string $value
     */
    private $value;

    const WALK_ON_WATER  = 'walk-on-water';
    const LABYRINTH      = 'labyrinth';

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::WALK_ON_WATER => 'walk-on-water',
        self::LABYRINTH   => 'labyrinth',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(string $value) {

        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * is walk on water?
     *
     * @return bool
     */
    public function walkOnWater(): bool {
        return $this->value === self::WALK_ON_WATER;
    }

    /**
     * Can Access Labyrinth
     *
     * @return bool
     */
    public function labyrinth(): bool {
        return $this->value === self::LABYRINTH;
    }
}
