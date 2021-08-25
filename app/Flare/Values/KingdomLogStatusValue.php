<?php

namespace App\Flare\Values;

class KingdomLogStatusValue {

    /**
     * @var string $value
     */
    private $value;

    const ATTACKED         = 'attacked kingdom';
    const LOST             = 'lost attack';
    const TAKEN            = 'taken kingdom';
    const LOST_KINGDOM     = 'lost kingdom';
    const KINGDOM_ATTACKED = 'kingdom attacked';
    const UNITS_RETURNING  = 'units returning';
    const BOMBS_DROPPED    = 'bombs dropped';

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::ATTACKED         => 'attacked kingdom',
        self::LOST             => 'lost attack',
        self::TAKEN            => 'taken kingdom',
        self::LOST_KINGDOM     => 'lost kingdom',
        self::KINGDOM_ATTACKED => 'kingdom attacked',
        self::UNITS_RETURNING  => 'units returning',
        self::BOMBS_DROPPED    => 'bombs dropped'
    ];

    /**
     * KingdomLogStatusValue constructor.
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
}
