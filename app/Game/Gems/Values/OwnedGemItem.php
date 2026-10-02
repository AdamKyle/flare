<?php

namespace App\Game\Gems\Values;

use App\Flare\Models\Item;

class OwnedGemItem
{
    /**
     * @param Item $item
     * @param GemAbilityOwnershipSource $source
     * @param int|null $setId
     * @param string|null $setName
     */
    public function __construct(
        public readonly Item $item,
        public readonly GemAbilityOwnershipSource $source,
        public readonly ?int $setId = null,
        public readonly ?string $setName = null,
    ) {}
}
