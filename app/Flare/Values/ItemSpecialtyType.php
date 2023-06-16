<?php

namespace App\Flare\Values;

use Exception;

class ItemSpecialtyType {

    /**
     * @var string $value
     */
    private string $value;

    const HELL_FORGED         = 'Hell Forged';
    const PURGATORY_CHAINS    = 'Purgatory Chains';
    const PIRATE_LORD_LEATHER = 'Pirate Lord Leather';

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::HELL_FORGED         => 'Hell Forged',
        self::PURGATORY_CHAINS    => 'Purgatory Chains',
        self::PIRATE_LORD_LEATHER => 'Pirate Lord Leather',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws Exception
     */
    public function __construct(string $value) {

        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }


    /**
     * Are we hell forged?
     *
     * @return bool
     */
    public function isHellForged(): bool {
        return $this->value === self::HELL_FORGED;
    }

    /**
     * Are we purgatory chains?
     *
     * @return bool
     */
    public function isPurgatoryChains(): bool {
        return $this->value === self::PURGATORY_CHAINS;
    }

    /**
     * Is Pirate Lord Leather?
     *
     * @return boolean
     */
    public function isPirateLordLeather(): bool {
        return $this->value === self::PIRATE_LORD_LEATHER;
    }
}
