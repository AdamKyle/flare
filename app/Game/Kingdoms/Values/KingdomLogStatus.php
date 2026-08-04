<?php

namespace App\Game\Kingdoms\Values;

enum KingdomLogStatus: int
{
    /**
     * @var string
     */
    case ATTACKED = 0;

    case LOST = 1;

    case TAKEN = 2;

    case LOST_KINGDOM = 3;

    case KINGDOM_ATTACKED = 4;

    case UNITS_RETURNING = 5;

    case BOMBS_DROPPED = 6;

    case OVER_POPULATED = 7;

    case NOT_WALKED = 8;

    case RESOURCES_REQUESTED = 9;

    case RESOURCES_LOST = 10;

    case CAPITAL_CITY_BUILDING_REQUEST = 11;

    case CAPITAL_CITY_UNIT_REQUEST = 12;

    /**
     * Attacked?
     */
    public function attackedKingdom(): bool
    {
        return $this === self::ATTACKED;
    }

    /**
     * Lost the attack?
     */
    public function lostAttack(): bool
    {
        return $this === self::LOST;
    }

    /**
     * Took the kingdom?
     */
    public function tookKingdom(): bool
    {
        return $this === self::TAKEN;
    }

    /**
     * Was defending kingdom attacked?
     */
    public function kingdomWasAttacked(): bool
    {
        return $this === self::KINGDOM_ATTACKED;
    }

    /**
     * Was defending kingdom lost?
     */
    public function lostKingdom(): bool
    {
        return $this === self::LOST_KINGDOM;
    }

    /**
     * Are units returning?
     */
    public function unitsReturning(): bool
    {
        return $this === self::UNITS_RETURNING;
    }

    /**
     * Were the bombs dropped?
     */
    public function bombsDropped(): bool
    {
        return $this === self::BOMBS_DROPPED;
    }

    /**
     * Were we overpopulated?
     */
    public function overPopulated(): bool
    {
        return $this === self::OVER_POPULATED;
    }

    /**
     * Has the kingdom not been walked?
     */
    public function notWalked(): bool
    {
        return $this === self::NOT_WALKED;
    }

    /**
     * Did we request resources?
     */
    public function requestedResources(): bool
    {
        return $this === self::RESOURCES_REQUESTED;
    }

    /**
     * Did we get a capital city request?
     */
    public function capitalCityBuildingRequest(): bool
    {
        return $this === self::CAPITAL_CITY_BUILDING_REQUEST;
    }

    /**
     * Did we get a capital city request?
     */
    public function capitalCityUnitRequest(): bool
    {
        return $this === self::CAPITAL_CITY_UNIT_REQUEST;
    }
}
