<?php

namespace App\Flare\Items\Transformers;

use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;
use App\Game\Gems\Traits\GetItemAtonements;
use League\Fractal\TransformerAbstract;

/**
 * Transformer for equippable items.
 *
 * Assumes the provided Item has already been passed through EquippableEnricher.
 * This transformer maps both base and enriched fields for API output.
 */
class BaseEquippableItemTransformer extends TransformerAbstract
{
    use GetItemAtonements, IsItemUnique;

    /**
     * Transforms an enriched Item model into an API-ready array.
     *
     * @param  Item  $item  ->item
     * @return array<string, mixed>
     */
    public function transform(Item $item): array
    {
        return [
            'item_id' => $item->id,
            'name' => $item->affix_name,
            'affix_count' => $item->affix_count,
            'holy_stack_stat_bonus' => $item->holy_stack_stat_bonus,
            'holy_stacks_applied' => $item->holy_stacks_applied,
            'is_unique' => $this->isUnique($item),
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
            'is_usable' => $item->usable,
            'holy_level' => $item->holy_level,
            'damages_kingdoms' => $item->damages_kingdoms,
            'description' => $item->description,
            'type' => $item->type,
            'cost' => $item->cost,
        ];
    }
}
