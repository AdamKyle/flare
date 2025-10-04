<?php

namespace App\Flare\Values;

use Exception;

class ItemSpecialtyType
{
    private string $value;

    const HELL_FORGED = 'Hell Forged';

    const PURGATORY_CHAINS = 'Purgatory Chains';

    const PIRATE_LORD_LEATHER = 'Pirate Lord Leather';

    const CORRUPTED_ICE = 'Corrupted Ice';

    const DELUSIONAL_SILVER = 'Delusional Silver';

    const TWISTED_EARTH = 'Twisted Earth';

    const FAITHLESS_PLATE = 'Faithless Plate';

    /**
     * @var string[]
     */
    protected static $values = [
        self::HELL_FORGED => 'Hell Forged',
        self::PURGATORY_CHAINS => 'Purgatory Chains',
        self::PIRATE_LORD_LEATHER => 'Pirate Lord Leather',
        self::CORRUPTED_ICE => 'Corrupted Ice',
        self::DELUSIONAL_SILVER => 'Delusional Silver',
        self::TWISTED_EARTH => 'Twisted Earth',
        self::FAITHLESS_PLATE => 'Faithless Plate',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(string $value)
    {

        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Get the values for selection.
     *
     * @return string[]
     */
    public static function getValuesForSelect(): array
    {
        return self::$values;
    }

    /**
     * Get the name of the specialty type.
     */
    public function getItemSpecialtyTypeName(): string
    {
        return self::$values[$this->value];
    }

    /**
     * Are we hell forged?
     */
    public function isHellForged(): bool
    {
        return $this->value === self::HELL_FORGED;
    }

    /**
     * Are we purgatory chains?
     */
    public function isPurgatoryChains(): bool
    {
        return $this->value === self::PURGATORY_CHAINS;
    }

    /**
     * Is Pirate Lord Leather?
     */
    public function isPirateLordLeather(): bool
    {
        return $this->value === self::PIRATE_LORD_LEATHER;
    }

    /**
     * Is Corrupted Ice
     */
    public function isCorruptedIce(): bool
    {
        return $this->value === self::CORRUPTED_ICE;
    }

    /**
     * Is Twisted earth
     */
    public function isTwistedEarth(): bool
    {
        return $this->value === self::TWISTED_EARTH;
    }

    /**
     * Is twisted delusional
     */
    public function isDelusionalSilver(): bool
    {
        return $this->value === self::DELUSIONAL_SILVER;
    }

    /**
     * Is Faithless Plate
     */
    public function isFaithlessPlate(): bool
    {
        return $this->value === self::FAITHLESS_PLATE;
    }

    /**
     * Get the gold cost for types that have one.
     *
     * Returns null when the type has no defined cost.
     */
    public function getCost(): ?int
    {
        return match ($this->value) {
            self::PIRATE_LORD_LEATHER => 75_000_000_000,
            self::CORRUPTED_ICE => 275_000_000_000,
            self::DELUSIONAL_SILVER => 280_000_000_000,
            self::FAITHLESS_PLATE => 300_000_000_000,
            default => null,

        };
    }
}
