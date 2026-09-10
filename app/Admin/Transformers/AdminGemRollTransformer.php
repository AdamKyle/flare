<?php

namespace App\Admin\Transformers;

use App\Flare\Models\Gem;
use App\Game\Gems\Transformers\RolledGemTransformer;

class AdminGemRollTransformer
{
    public function __construct(
        private readonly RolledGemTransformer $rolledGemTransformer,
    ) {}

    /**
     * Transform a Gem roll into its Admin HTTP representation.
     */
    public function transform(Gem $gem, bool $isActive): array
    {
        return $this->rolledGemTransformer->transform($gem, $isActive);
    }
}
