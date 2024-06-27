<?php

namespace App\Flare\Values;

use Exception;

class KingdomLogStatusValue {

    /**
     * @var string $value
     */
    private $value;

    const ATTACKED            = 0;
    const LOST                = 1;
    const TAKEN               = 2;
    const LOST_KINGDOM        = 3;
    const KINGDOM_ATTACKED    = 4;
    const UNITS_RETURNING     = 5;
    const BOMBS_DROPPED       = 6;
    const OVER_POPULATED      = 7;
    const NOT_WALKED          = 8;
    const RESOURCES_REQUESTED = 9;
    const RESOURCES_LOST      = 10;
    const CAPITAL_CITY_REQUEST = 11;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::ATTACKED            => 0,
        self::LOST                => 1,
        self::TAKEN               => 2,
        self::LOST_KINGDOM        => 3,
        self::KINGDOM_ATTACKED    => 4,
        self::UNITS_RETURNING     => 5,
        self::BOMBS_DROPPED       => 6,
        self::OVER_POPULATED      => 7,
        self::NOT_WALKED          => 8,
        self::RESOURCES_REQUESTED => 9,
        self::RESOURCES_LOST      => 10,
        self::CAPITAL_CITY_REQUEST => 11,
    ];

    /**
     * KingdomLogStatusValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Attacked?
     *
     * @return bool
     */
    public function attackedKingdom(): bool {
        return $this->value === self::ATTACKED;
    }

    /**
     * Lost the attack?
     *
     * @return bool
     */
    public function lostAttack(): bool {
        return $this->value === self::LOST;
    }

    /**
     * Took the kingdom?
     *
     * @return bool
     */
    public function tookKingdom(): bool {
        return $this->value === self::TAKEN;
    }

    /**
     * Was defending kingdom attacked?
     *
     * @return bool
     */
    public function kingdomWasAttacked(): bool {
        return $this->value === self::KINGDOM_ATTACKED;
    }

    /**
     * Was defending kingdom lost?
     *
     * @return bool
     */
    public function lostKingdom(): bool {
        return $this->value === self::LOST_KINGDOM;
    }

    /**
     * Are units returning?
     *
     * @return bool
     */
    public function unitsReturning(): bool {
        return $this->value === self::UNITS_RETURNING;
    }

    /**
     * Were the bombs dropped?
     *
     * @return bool
     */
    public function bombsDropped(): bool {
        return $this->value === self::BOMBS_DROPPED;
    }

    /**
     * Were we overpopulated?
     *
     * @return bool
     */
    public function overPopulated(): bool {
        return $this->value === self::OVER_POPULATED;
    }

    /**
     * Has the kingdom not been walked?
     *
     * @return bool
     */
    public function notWalked(): bool {
        return $this->value === self::NOT_WALKED;
    }

    /**
     * Did we request resources?
     *
     * @return bool
     */
    public function requestedResources(): bool {
        return $this->value === self::RESOURCES_REQUESTED;
    }

    /**
     * Did we get a capital city request?
     *
     * @return bool
     */
    public function capitalCityRequest(): bool {
        return $this->value === self::CAPITAL_CITY_REQUEST;
    }
}
