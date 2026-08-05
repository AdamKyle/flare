<?php

namespace App\Game\Core\Items\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use League\Fractal\TransformerAbstract;

class CraftingItemPreviewTransformer extends TransformerAbstract
{
    public function __construct(private readonly ItemEnricherFactory $itemEnricherFactory) {}

    public function transform(Item $item, ?int $inventorySlotId = null): array
    {
        $enrichedItem = $this->itemEnricherFactory->buildItem(clone $item);

        return [
            'item_id' => $enrichedItem->id,
            'inventory_slot_id' => $inventorySlotId,
            'name' => $enrichedItem->name,
            'description' => $enrichedItem->description,
            'type' => $enrichedItem->type,
            'base_damage' => $enrichedItem->total_damage ?? $enrichedItem->base_damage ?? 0,
            'base_damage_mod' => $enrichedItem->base_damage_mod ?? 0.0,
            'base_ac' => $enrichedItem->total_defence ?? $enrichedItem->base_ac ?? 0,
            'base_ac_mod' => $enrichedItem->base_ac_mod ?? 0.0,
            'base_healing' => $enrichedItem->total_healing ?? $enrichedItem->base_healing ?? 0,
            'base_healing_mod' => $enrichedItem->base_healing_mod ?? 0.0,
            'str_modifier' => $enrichedItem->str_mod,
            'dur_modifier' => $enrichedItem->dur_mod,
            'dex_modifier' => $enrichedItem->dex_mod,
            'chr_modifier' => $enrichedItem->chr_mod,
            'int_modifier' => $enrichedItem->int_mod,
            'agi_modifier' => $enrichedItem->agi_mod,
            'focus_modifier' => $enrichedItem->focus_mod,
            'ambush_chance' => $enrichedItem->ambush_chance,
            'ambush_resistance_chance' => $enrichedItem->ambush_resistance,
            'counter_chance' => $enrichedItem->counter_chance,
            'counter_resistance_chance' => $enrichedItem->counter_resistance,
            'is_mythic' => $enrichedItem->is_mythic,
            'is_cosmic' => $enrichedItem->is_cosmic,
            'is_unique' => $enrichedItem->is_unique,
            'affix_count' => $enrichedItem->affix_count,
            'holy_stacks' => $enrichedItem->holy_stacks,
            'holy_stacks_applied' => $enrichedItem->holy_stacks_applied,
            'socket_count' => $enrichedItem->socket_count,
            'usable' => $enrichedItem->usable,
            'holy_level' => $enrichedItem->holy_level,
            'damages_kingdoms' => $enrichedItem->damages_kingdoms,
            'item_prefix' => $this->transformAffix($enrichedItem->itemPrefix),
            'item_suffix' => $this->transformAffix($enrichedItem->itemSuffix),
        ];
    }

    private function transformAffix(?ItemAffix $affix): ?array
    {
        if (is_null($affix)) {
            return null;
        }

        return [
            'id' => $affix->id,
            'name' => $affix->name,
        ];
    }
}
