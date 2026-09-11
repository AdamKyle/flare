<?php

namespace App\Game\Gems\Values;

class ResolvedAreaGemRarityEffects
{
    /**
     * @param float $unique
     * @param float $mythic
     * @param float $cosmic
     */
    public function __construct(
        private readonly float $unique,
        private readonly float $mythic,
        private readonly float $cosmic,
    ) {}

    /**
     * Build a no-effect resolved rarity result.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self(0.0, 0.0, 0.0);
    }

    /**
     * The resolved Unique item drop chance increase.
     *
     * @return float
     */
    public function unique(): float
    {
        return $this->unique;
    }

    /**
     * The resolved Mythic item drop chance increase.
     *
     * @return float
     */
    public function mythic(): float
    {
        return $this->mythic;
    }

    /**
     * The resolved Cosmic item drop chance increase.
     *
     * @return float
     */
    public function cosmic(): float
    {
        return $this->cosmic;
    }

    /**
     * Serialize this result into its factual field shape.
     *
     * @return array
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
