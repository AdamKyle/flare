<?php

namespace App\Game\Core\Items\Values;

enum ItemSpecialtyType: string
{
    case HELL_FORGED = 'Hell Forged';
    case PURGATORY_CHAINS = 'Purgatory Chains';
    case PIRATE_LORD_LEATHER = 'Pirate Lord Leather';
    case CORRUPTED_ICE = 'Corrupted Ice';
    case DELUSIONAL_SILVER = 'Delusional Silver';
    case TWISTED_EARTH = 'Twisted Earth';
    case FAITHLESS_PLATE = 'Faithless Plate';
    case LABYRINTH_CLOTH = 'Labyrinth Cloth';

    public static function getValuesForSelect(): array
    {
        return array_combine(
            array_map(static fn (self $type): string => $type->value, self::cases()),
            array_map(static fn (self $type): string => $type->value, self::cases()),
        );
    }

    public function getItemSpecialtyTypeName(): string
    {
        return $this->value;
    }

    public function isHellForged(): bool
    {
        return $this === self::HELL_FORGED;
    }

    public function isPurgatoryChains(): bool
    {
        return $this === self::PURGATORY_CHAINS;
    }

    public function isPirateLordLeather(): bool
    {
        return $this === self::PIRATE_LORD_LEATHER;
    }

    public function isCorruptedIce(): bool
    {
        return $this === self::CORRUPTED_ICE;
    }

    public function isTwistedEarth(): bool
    {
        return $this === self::TWISTED_EARTH;
    }

    public function isDelusionalSilver(): bool
    {
        return $this === self::DELUSIONAL_SILVER;
    }

    public function isFaithlessPlate(): bool
    {
        return $this === self::FAITHLESS_PLATE;
    }

    public function isLabyrinthCloth(): bool
    {
        return $this === self::LABYRINTH_CLOTH;
    }

    public function getCost(): ?int
    {
        return match ($this) {
            self::PIRATE_LORD_LEATHER, self::LABYRINTH_CLOTH => 75_000_000_000,
            self::CORRUPTED_ICE => 275_000_000_000,
            self::DELUSIONAL_SILVER => 280_000_000_000,
            self::FAITHLESS_PLATE => 300_000_000_000,
            default => null,
        };
    }
}
