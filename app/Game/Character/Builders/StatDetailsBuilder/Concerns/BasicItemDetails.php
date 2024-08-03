<?php

namespace App\Game\Character\Builders\StatDetailsBuilder\Concerns;

use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;

trait BasicItemDetails
{
    use IsItemUnique;

    /**
     * Create basic item details.
     */
    protected function getBasicDetailsOfItem(Item $item): array
    {
        return [
            'name' => $item->affix_name,
            'type' => $item->type,
            'affix_count' => $item->affix_count,
            'is_unique' => $this->isUnique($item),
            'holy_stacks_applied' => $item->holy_stacks_applied,
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
        ];
    }
}
