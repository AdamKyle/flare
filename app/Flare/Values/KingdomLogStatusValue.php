<?php

namespace App\Flare\Values;

use Exception;

class KingdomLogStatusValue
{
    /**
     * @var string
     */
    private $value;

    const ATTACKED = 0;

    const LOST = 1;

    const TAKEN = 2;

    const LOST_KINGDOM = 3;

    const KINGDOM_ATTACKED = 4;

    const UNITS_RETURNING = 5;

    const BOMBS_DROPPED = 6;

    const OVER_POPULATED = 7;

    const NOT_WALKED = 8;

    const RESOURCES_REQUESTED = 9;

    const RESOURCES_LOST = 10;

    const CAPITAL_CITY_BUILDING_REQUEST = 11;

    const CAPITAL_CITY_UNIT_REQUEST = 12;

    /**
     * @var string[]
     */
    protected static $values = [
        self::ATTACKED => 0,
        self::LOST => 1,
        self::TAKEN => 2,
        self::LOST_KINGDOM => 3,
        self::KINGDOM_ATTACKED => 4,
        self::UNITS_RETURNING => 5,
        self::BOMBS_DROPPED => 6,
        self::OVER_POPULATED => 7,
        self::NOT_WALKED => 8,
        self::RESOURCES_REQUESTED => 9,
        self::CAPITAL_CITY_BUILDING_REQUEST => 11,
        self::CAPITAL_CITY_UNIT_REQUEST => 12,
    ];

    /**
     * KingdomLogStatusValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Attacked?
     */
    public function attackedKingdom(): bool
    {
        return $this->value === self::ATTACKED;
    }

    /**
     * Lost the attack?
     */
    public function lostAttack(): bool
    {
        return $this->value === self::LOST;
    }

    /**
     * Took the kingdom?
     */
    public function tookKingdom(): bool
    {
        return $this->value === self::TAKEN;
    }

    /**
     * Was defending kingdom attacked?
     */
    public function kingdomWasAttacked(): bool
    {
        return $this->value === self::KINGDOM_ATTACKED;
    }

    /**
     * Was defending kingdom lost?
     */
    public function lostKingdom(): bool
    {
        return $this->value === self::LOST_KINGDOM;
    }

    /**
     * Are units returning?
     */
    public function unitsReturning(): bool
    {
        return $this->value === self::UNITS_RETURNING;
    }

    /**
     * Were the bombs dropped?
     */
    public function bombsDropped(): bool
    {
        return $this->value === self::BOMBS_DROPPED;
    }

    /**
     * Were we overpopulated?
     */
    public function overPopulated(): bool
    {
        return $this->value === self::OVER_POPULATED;
    }

    /**
     * Has the kingdom not been walked?
     */
    public function notWalked(): bool
    {
        return $this->value === self::NOT_WALKED;
    }

    /**
     * Did we request resources?
     */
    public function requestedResources(): bool
    {
        return $this->value === self::RESOURCES_REQUESTED;
    }

    /**
     * Did we get a capital city request?
     */
    public function capitalCityBuildingRequest(): bool
    {
        return $this->value === self::CAPITAL_CITY_BUILDING_REQUEST;
    }

    /**
     * Did we get a capital city request?
     */
    public function capitalCityUnitRequest(): bool
    {
        return $this->value === self::CAPITAL_CITY_UNIT_REQUEST;
    }
}
