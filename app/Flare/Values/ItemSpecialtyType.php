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
    const CORRUPTED_ICE       = 'Corrupted Ice';

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::HELL_FORGED         => 'Hell Forged',
        self::PURGATORY_CHAINS    => 'Purgatory Chains',
        self::PIRATE_LORD_LEATHER => 'Pirate Lord Leather',
        self::CORRUPTED_ICE       => 'Corrupted Ice',
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
     * Get the values for selection.
     *
     * @return string[]
     */
    public static function getValuesForSelect(): array {
        return self::$values;
    }

    /**
     * Get the name of the specialty type.
     *
     * @return string
     */
    public function getItemSpecialtyTypeName(): string {
        return self::$values[$this->value];
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

    /**
     * Is Corrupted Ice
     *
     * @return boolean
     */
    public function isCorruptedIce(): bool {
        return $this->value === self::CORRUPTED_ICE;
    }
}
