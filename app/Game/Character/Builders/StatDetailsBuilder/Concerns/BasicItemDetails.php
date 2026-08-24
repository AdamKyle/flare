<?php

namespace App\Game\Character\Builders\StatDetailsBuilder\Concerns;

use App\Flare\Models\Item;
use App\Game\Core\Items\Values\ItemUniqueness;

trait BasicItemDetails
{
    /**
     * Create basic item details.
     */
    protected function getBasicDetailsOfItem(Item $item): array
    {
        return [
            'name' => $item->affix_name,
            'type' => $item->type,
            'affix_count' => $item->affix_count,
            'is_unique' => ItemUniqueness::fromItem($item)->isUnique(),
            'holy_stacks_applied' => $item->holy_stacks_applied,
            'max_holy_stacks' => $item->holy_stacks,
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
        ];
    }
}
