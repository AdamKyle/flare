<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameSkill;
use Facades\App\Flare\Calculators\SellItemCalculator;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Item;

class ItemTransfromer extends TransformerAbstract {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Item $item) {

        return [
            'id'                               => $item->id,
            'name'                             => $item->affix_name,
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
            'skill_name'                       => $item->skill_training_name,
            'skill_training_bonus'             => $item->skill_training_bonus,
            'skill_bonus'                      => $item->skill_bonus,
            'item_prefix'                      => $item->itemPrefix,
            'item_suffix'                      => $item->itemSuffix,
            'usable'                           => $item->usable,
            'crafting_type'                    => $item->crafting_type,
            'skill_level_req'                  => $item->skill_level_required,
            'skill_level_trivial'              => $item->skill_level_trivial,
            'cost'                             => SellItemCalculator::fetchSalePriceWithAffixes($item),
            'usable'                           => $item->usable,
            'base_damage_mod_bonus'            => $item->getTotalBaseDamageMod(),
            'base_healing_mod_bonus'           => $item->base_healing_mod_bonus,
            'base_ac_mod_bonus'                => $item->base_ac_mod_bonus,
            'fight_time_out_mod_bonus'         => $item->getTotalFightTimeOutMod(),
            'move_time_out_mod_bonus'          => $item->move_time_out_mod_bonus,
            'damages_kingdoms'                 => $item->damages_kingdoms,
            'kingdom_damage'                   => $item->kingdom_damage,
            'lasts_for'                        => $item->lasts_for,
            'stat_increase'                    => $item->stat_increase,
            'increase_stat_by'                 => $item->increase_stat_by,
            'affects_skills'                   => GameSkill::where('type', $item->affects_skill_type)->pluck('name')->toArray(),
            'can_resurrect'                    => $item->can_resurrect,
            'resurrection_chance'              => $item->resurrection_chance,
            'spell_evasion'                    => $item->spell_evasion,
            'artifact_annulment'               => $item->artifact_annulment,
            'increase_skill_bonus_by'          => $item->increase_skill_bonus_by,
            'increase_skill_training_bonus_by' => $item->increase_skill_training_bonus_by,
        ];
    }
}
