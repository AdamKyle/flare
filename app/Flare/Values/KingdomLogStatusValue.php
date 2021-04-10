<?php

namespace App\Flare\Values;

class KingdomLogStatusValue {

    /**
     * @var string $value
     */
    private $value;

    const ATTACKED = 'attacked';
    const LOST     = 'lost';
    const TAKEN    = 'taken';

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::ATTACKED => 'attacked',
        self::LOST     => 'lost',
        self::TAKEN    => 'taken',
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
    public function attacked(): bool {
        return $this->value === self::ATTACKED;
    }

    /**
     * Lost the attack?
     *
     * @return bool
     */
    public function lost(): bool {
        return $this->value === self::LOST;
    }

    /**
     * Took the kingdom?
     *
     * @return bool
     */
    public function took(): bool {
        return $this->value === self::TAKEN;
    }
}
