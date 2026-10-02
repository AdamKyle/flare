<?php

namespace App\Game\Gems\Values;

class DuplicateGemAbilityReport
{
    /**
     * @param array $duplicates
     */
    public function __construct(
        public readonly array $duplicates,
    ) {}

    /**
     * Serialize every duplicate Active Gem Ability group into the warning payload.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_map(
            fn (DuplicateGemAbility $duplicate): array => $duplicate->toArray(),
            $this->duplicates,
        );
    }
}
