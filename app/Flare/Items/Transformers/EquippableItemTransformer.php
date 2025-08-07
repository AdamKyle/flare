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
class EquippableItemTransformer extends TransformerAbstract
{
    use GetItemAtonements, IsItemUnique;

    /**
     * Transforms an enriched Item model into an API-ready array.
     *
     * @param Item $item
     * @return array<string, mixed>
     */
    public function transform(Item $item): array
    {
        return [
            'id'                        => $item->id,
            'name'                      => $item->affix_name,
            'affix_count'               => $item->affix_count,
            'description'               => nl2br(e($item->description)),

            // Base values
            'raw_damage'                => $item->base_damage,
            'raw_ac'                    => $item->base_ac,
            'raw_healing'              => $item->base_healing,

            // Enriched values
            'base_damage'               => $item->total_damage,
            'base_ac'                   => $item->total_defence,
            'base_healing'              => $item->total_healing,

            'base_damage_mod'           => $item->base_damage_mod,
            'base_ac_mod'               => $item->base_ac_mod,
            'base_healing_mod'          => $item->base_healing_mod,

            'base_damage_mod_bonus'     => $item->base_damage_mod_bonus,
            'base_healing_mod_bonus'    => $item->base_healing_mod_bonus,
            'base_ac_mod_bonus'         => $item->base_ac_mod_bonus,

            'affix_damage_reduction'    => $item->affix_damage_reduction,

            // Stat modifiers (enriched)
            'str_modifier'              => $item->str_mod,
            'dur_modifier'              => $item->dur_mod,
            'int_modifier'              => $item->int_mod,
            'dex_modifier'              => $item->dex_mod,
            'chr_modifier'              => $item->chr_mod,
            'agi_modifier'              => $item->agi_mod,
            'focus_modifier'            => $item->focus_mod,

            // Meta
            'type'                      => $item->type,
            'default_position'          => $item->default_position,

            // Skills
            'skill_name'                => $item->skill_name,
            'skill_training_bonus'      => $item->skill_training_bonus,
            'skill_bonus'               => $item->skill_bonus,
            'skill_summary'             => $item->skill_summary,

            // Affixes
            'item_prefix'               => $item->itemPrefix,
            'item_suffix'               => $item->itemSuffix,

            // Crafting / reqs
            'crafting_type'             => $item->crafting_type,
            'skill_level_req'           => $item->skill_level_required,
            'skill_level_trivial'       => $item->skill_level_trivial,
            'cost'                      => $item->cost,

            // Mod bonuses
            'fight_time_out_mod_bonus'  => $item->fight_time_out_mod_bonus,
            'move_time_out_mod_bonus'   => $item->move_time_out_mod_bonus,

            // Rarity / quality
            'is_unique'                 => $this->isUnique($item),
            'is_mythic'                 => $item->is_mythic,
            'is_cosmic'                 => $item->is_cosmic,

            // Holy stacks
            'holy_level'                => $item->holy_level,
            'holy_stacks'               => $item->holy_stacks,
            'applied_stacks'            => $item->appliedHolyStacks,
            'holy_stack_devouring_darkness' => $item->holy_stack_devouring_darkness,
            'holy_stack_stat_bonus'     => $item->holy_stack_stat_bonus,
            'holy_stacks_applied'       => $item->holy_stacks_applied,

            // Ambush and Counter
            'ambush_chance'             => $item->ambush_chance,
            'ambush_resistance_chance'  => $item->ambush_resistance,
            'counter_chance'            => $item->counter_chance,
            'counter_resistance_chance' => $item->counter_resistance,

            // Devouring attributes
            'devouring_light'           => $item->devouring_light,
            'devouring_darkness'        => $item->devouring_darkness,

            // Enriched affix damage totals
            'total_stackable_affix_damage'    => $item->total_stackable_affix_damage,
            'total_non_stacking_affix_damage' => $item->total_non_stacking_affix_damage,
            'total_irresistible_affix_damage' => $item->total_irresistible_affix_damage,

            // Sockets and Atonements
            'sockets'         => $item->sockets,
            'socket_amount'   => $item->socket_count,
            'item_atonements' => $this->getElementAtonement($item),
        ];
    }
}
