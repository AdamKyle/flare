<?php

namespace App\Game\Gems\Progression\Values;

/**
 * Immutable base/global/personal/effective breakdown for a single additive
 * positive Gem effect field, used by the Character-facing Gem Progress
 * read model.
 */
class GemFieldProgressionBreakdown
{
    /**
     * @param string $field
     * @param float $base
     * @param float $global
     * @param float $personal
     * @param float $effective
     */
    public function __construct(
        private readonly string $field,
        private readonly float $base,
        private readonly float $global,
        private readonly float $personal,
        private readonly float $effective,
    ) {}

    /**
     * Determine whether this field currently contributes a positive rolled or effective value.
     *
     * @return bool
     */
    public function isApplicable(): bool
    {
        return $this->base > 0.0 || $this->effective > 0.0;
    }

    /**
     * The final base plus global plus personal effective value.
     *
     * @return float
     */
    public function effective(): float
    {
        return $this->effective;
    }

    /**
     * The base plus global value, before any personal progression is added.
     *
     * @return float
     */
    public function globalEffective(): float
    {
        return $this->base + $this->global;
    }

    /**
     * Serialize this breakdown into its factual field shape.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'base' => $this->base,
            'global' => $this->global,
            'personal' => $this->personal,
            'global_effective' => $this->globalEffective(),
            'effective' => $this->effective,
        ];
    }
}
