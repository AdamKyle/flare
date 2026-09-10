<?php

namespace App\Game\Gems\Values;

/**
 * Immutable resolved Unique/Mythic/Cosmic Gem rarity modifiers for a Game Map/Location gameplay context.
 */
class ResolvedAreaGemRarityEffects
{
    /**
     * @param  float  $unique  Resolved Unique item drop chance increase.
     * @param  float  $mythic  Resolved Mythic item drop chance increase.
     * @param  float  $cosmic  Resolved Cosmic item drop chance increase.
     */
    public function __construct(
        private readonly float $unique,
        private readonly float $mythic,
        private readonly float $cosmic,
    ) {}

    /**
     * Build a no-effect resolved rarity result.
     */
    public static function none(): self
    {
        return new self(0.0, 0.0, 0.0);
    }

    /**
     * The resolved Unique item drop chance increase.
     */
    public function unique(): float
    {
        return $this->unique;
    }

    /**
     * The resolved Mythic item drop chance increase.
     */
    public function mythic(): float
    {
        return $this->mythic;
    }

    /**
     * The resolved Cosmic item drop chance increase.
     */
    public function cosmic(): float
    {
        return $this->cosmic;
    }

    /**
     * Serialize this result into its factual field shape.
     *
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'unique' => $this->unique,
            'mythic' => $this->mythic,
            'cosmic' => $this->cosmic,
        ];
    }
}
