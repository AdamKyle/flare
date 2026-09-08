<?php

namespace App\Admin\Items\Transformers;

use App\Flare\Models\Item;
use League\Fractal\TransformerAbstract;

class ItemListTransformer extends TransformerAbstract
{
    /**
     * Transform an Item into its Admin list-row representation.
     */
    public function transform(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'can_craft' => $item->can_craft,
            'usable' => $item->usable,
            'market_sellable' => $item->market_sellable,
            'can_drop' => $item->can_drop,
            'base_damage' => $item->base_damage,
            'base_ac' => $item->base_ac,
            'base_healing' => $item->base_healing,
            'base_damage_mod' => $item->base_damage_mod,
            'base_ac_mod' => $item->base_ac_mod,
            'base_healing_mod' => $item->base_healing_mod,
            'cost' => $item->cost,
            'gold_bars_cost' => $item->gold_bars_cost,
            'gold_dust_cost' => $item->gold_dust_cost,
            'shards_cost' => $item->shards_cost,
            'skill_level_required' => $item->skill_level_required,
            'skill_level_trivial' => $item->skill_level_trivial,
            'ambush_chance' => $item->ambush_chance,
            'ambush_resistance' => $item->ambush_resistance,
            'counter_chance' => $item->counter_chance,
            'counter_resistance' => $item->counter_resistance,
            'item_skill' => $this->transformItemSkill($item),
            'specialty_type' => $item->specialty_type,
            'alchemy_type' => $item->alchemy_type,
            'drop_location' => $this->transformDropLocation($item),
            'effect' => $item->effect,
            'unlocks_class' => $this->transformUnlocksClass($item),
        ];
    }

    /**
     * Transform the related Item Skill into its compact identity representation.
     */
    private function transformItemSkill(Item $item): ?array
    {
        if (is_null($item->item_skill_id) || is_null($item->itemSkill)) {
            return null;
        }

        return [
            'id' => $item->itemSkill->id,
            'name' => $item->itemSkill->name,
        ];
    }

    /**
     * Transform the related drop Location into its compact identity representation.
     */
    private function transformDropLocation(Item $item): ?array
    {
        if (is_null($item->drop_location_id) || is_null($item->dropLocation)) {
            return null;
        }

        return [
            'id' => $item->dropLocation->id,
            'name' => $item->dropLocation->name,
        ];
    }

    /**
     * Transform the unlocked Class into its compact identity representation.
     */
    private function transformUnlocksClass(Item $item): ?array
    {
        if (is_null($item->unlocks_class_id) || is_null($item->unlocksClass)) {
            return null;
        }

        return [
            'id' => $item->unlocksClass->id,
            'name' => $item->unlocksClass->name,
        ];
    }
}
