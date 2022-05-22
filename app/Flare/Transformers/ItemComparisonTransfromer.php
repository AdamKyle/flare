<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameSkill;
use App\Flare\Traits\IsItemUnique;
use Facades\App\Flare\Calculators\SellItemCalculator;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Item;

class ItemComparisonTransfromer extends TransformerAbstract {

    use IsItemUnique;

    /**
     * Gets the response data for the character sheet
     *
     * @param Item $item
     * @return array
     */
    public function transform(Item $item): array {

        return [
            'id'                               => $item->id,
            'affix_name'                       => $item->affix_name,
            'base_damage'                      => $item->getTotalDamage(),
            'base_ac'                          => $item->getTotalDefence(),
            'base_healing'                     => $item->getTotalHealing(),
            'base_damage_mod'                  => is_null($item->base_damage_mod) ? 0.0 : $item->base_damage_mod,
            'base_ac_mod'                      => $item->base_ac_mod,
            'base_healing_mod'                 => $item->base_healing_mod,
            'str_modifier'                     => $item->getTotalPercentageForStat('str'),
            'dur_modifier'                     => $item->getTotalPercentageForStat('dur'),
            'int_modifier'                     => $item->getTotalPercentageForStat('int'),
            'dex_modifier'                     => $item->getTotalPercentageForStat('dex'),
            'chr_modifier'                     => $item->getTotalPercentageForStat('chr'),
            'agi_modifier'                     => $item->getTotalPercentageForStat('agi'),
            'focus_modifier'                   => $item->getTotalPercentageForStat('focus'),
            'type'                             => $item->type,
            'default_position'                 => $item->default_position,
            'crafting_type'                    => $item->crafting_type,
            'skill_level_req'                  => $item->skill_level_required,
            'skill_level_trivial'              => $item->skill_level_trivial,
            'cost'                             => SellItemCalculator::fetchSalePriceWithAffixes($item),
            'shop_cost'                        => $item->cost,
            'base_damage_mod_bonus'            => $item->base_damage_mod_bonus,
            'base_healing_mod_bonus'           => $item->base_healing_mod_bonus,
            'base_ac_mod_bonus'                => $item->base_ac_mod_bonus,
            'fight_time_out_mod_bonus'         => $item->getTotalFightTimeOutMod(),
            'resurrection_chance'              => $item->resurrection_chance,
            'spell_evasion'                    => $item->spell_evasion,
            'artifact_annulment'               => $item->artifact_annulment,
            'is_unique'                        => $this->isUnique($item),
            'affix_count'                      => $item->affix_count,
            'min_cost'                         => SellItemCalculator::fetchMinPrice($item),
            'holy_level'                       => $item->holy_level,
            'holy_stacks'                      => $item->holy_stacks,
            'holy_stack_devouring_darkness'    => $item->holy_stack_devouring_darkness,
            'holy_stack_stat_bonus'            => $item->holy_stack_stat_bonus,
            'holy_stacks_applied'              => $item->holy_stacks_applied,
            'ambush_chance'                    => $item->ambush_chance,
            'ambush_resistance_chance'         => $item->ambush_resistance,
            'counter_chance'                   => $item->counter_chance,
            'counter_resistance_chance'        => $item->counter_resistance,
        ];
    }
}
