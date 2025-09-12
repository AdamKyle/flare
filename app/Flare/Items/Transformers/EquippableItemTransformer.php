<?php

namespace App\Flare\Items\Transformers;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
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
     * @param InventorySlot|SetSlot $slot ->item
     * @return array<string, mixed>
     */
    public function transform(InventorySlot | SetSlot $slot): array
    {
        return [
            'slot_id'                   => $slot->id,
            'item_id'                   => $slot->item->id,
            'name'                      => $slot->item->affix_name,
            'affix_count'               => $slot->item->affix_count,
            'description'               => nl2br(e($slot->item->description)),
            'raw_damage'                => $slot->item->base_damage,
            'raw_ac'                    => $slot->item->base_ac,
            'raw_healing'               => $slot->item->base_healing,
            'base_damage'               => $slot->item->total_damage,
            'base_ac'                   => $slot->item->total_defence,
            'base_healing'              => $slot->item->total_healing,
            'base_damage_mod'           => $slot->item->base_damage_mod,
            'base_ac_mod'               => $slot->item->base_ac_mod,
            'base_healing_mod'          => $slot->item->base_healing_mod,
            'str_modifier'              => $slot->item->str_mod,
            'dur_modifier'              => $slot->item->dur_mod,
            'int_modifier'              => $slot->item->int_mod,
            'dex_modifier'              => $slot->item->dex_mod,
            'chr_modifier'              => $slot->item->chr_mod,
            'agi_modifier'              => $slot->item->agi_mod,
            'focus_modifier'            => $slot->item->focus_mod,
            'type'                      => $slot->item->type,
            'default_position'          => $slot->item->default_position,
            'skill_name'                => $slot->item->skill_name,
            'skill_training_bonus'      => $slot->item->skill_training_bonus,
            'skill_bonus'               => $slot->item->skill_bonus,
            'skill_summary'             => $slot->item->skill_summary,
            'item_prefix'               => $slot->item->itemPrefix,
            'item_suffix'               => $slot->item->itemSuffix,
            'crafting_type'             => $slot->item->crafting_type,
            'skill_level_req'           => $slot->item->skill_level_required,
            'skill_level_trivial'       => $slot->item->skill_level_trivial,
            'cost'                      => $slot->item->cost,
            'fight_time_out_mod_bonus'  => $slot->item->fight_time_out_mod_bonus,
            'move_time_out_mod_bonus'   => $slot->item->move_time_out_mod_bonus,
            'is_unique'                 => $this->isUnique($slot->item),
            'is_mythic'                 => $slot->item->is_mythic,
            'is_cosmic'                 => $slot->item->is_cosmic,
            'holy_level'                => $slot->item->holy_level,
            'holy_stacks'               => $slot->item->holy_stacks,
            'applied_stacks'            => $slot->item->appliedHolyStacks,
            'holy_stack_devouring_darkness' => $slot->item->holy_stack_devouring_darkness,
            'holy_stack_stat_bonus'     => $slot->item->holy_stack_stat_bonus,
            'holy_stacks_applied'       => $slot->item->holy_stacks_applied,
            'ambush_chance'             => $slot->item->ambush_chance,
            'ambush_resistance_chance'  => $slot->item->ambush_resistance,
            'counter_chance'            => $slot->item->counter_chance,
            'counter_resistance_chance' => $slot->item->counter_resistance,
            'devouring_light'           => $slot->item->devouring_light,
            'devouring_darkness'        => $slot->item->devouring_darkness,
            'total_stackable_affix_damage'    => $slot->item->total_stackable_affix_damage,
            'total_non_stacking_affix_damage' => $slot->item->total_non_stacking_affix_damage,
            'total_irresistible_affix_damage' => $slot->item->total_irresistible_affix_damage,
            'sockets'         => $slot->item->sockets,
            'socket_amount'   => $slot->item->socket_count,
            'item_atonements' => $this->getElementAtonement($slot->item),
            'spell_evasion' => $slot->item->spell_evasion,
            'healing_reduction' => $slot->item->healing_reduction,
            'affix_damage_reduction' => $slot->item->affix_damage_reduction,
            'resurrection_chance' => $slot->item->resurrection_chance,
        ];
    }
}
