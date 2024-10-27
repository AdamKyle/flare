<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;
use App\Game\Gems\Traits\GetItemAtonements;
use Facades\App\Flare\Calculators\SellItemCalculator;
use League\Fractal\TransformerAbstract;

class ItemTransformer extends TransformerAbstract
{
    use GetItemAtonements, IsItemUnique;

    /**
     * Gets the response data for the character sheet
     */
    public function transform(Item $item): array
    {

        return [
            'id' => $item->id,
            'name' => $item->affix_name,
            'affix_count' => $item->affix_count,
            'description' => nl2br(e($item->description)),
            'raw_damage' => $item->base_damage,
            'raw_ac' => $item->base_ac,
            'raw_healing' => $item->base_healing,
            'base_damage' => $item->getTotalDamage(),
            'base_ac' => $item->getTotalDefence(),
            'base_healing' => $item->getTotalHealing(),
            'base_damage_mod' => is_null($item->base_damage_mod) ? 0.0 : $item->base_damage_mod,
            'base_ac_mod' => $item->base_ac_mod,
            'base_healing_mod' => $item->base_healing_mod,
            'str_modifier' => $item->getTotalPercentageForStat('str'),
            'dur_modifier' => $item->getTotalPercentageForStat('dur'),
            'int_modifier' => $item->getTotalPercentageForStat('int'),
            'dex_modifier' => $item->getTotalPercentageForStat('dex'),
            'chr_modifier' => $item->getTotalPercentageForStat('chr'),
            'agi_modifier' => $item->getTotalPercentageForStat('agi'),
            'focus_modifier' => $item->getTotalPercentageForStat('focus'),
            'type' => $item->type,
            'default_position' => $item->default_position,
            'skill_name' => $item->skill_name,
            'skill_training_bonus' => $item->skill_training_bonus,
            'skill_bonus' => $item->skill_bonus,
            'item_prefix' => $item->itemPrefix,
            'item_suffix' => $item->itemSuffix,
            'usable' => $item->usable,
            'can_use_on_other_items' => $item->can_use_on_other_items,
            'crafting_type' => $item->crafting_type,
            'skill_level_req' => $item->skill_level_required,
            'skill_level_trivial' => $item->skill_level_trivial,
            'cost' => $item->cost,
            'base_damage_mod_bonus' => $item->getTotalBaseDamageMod(),
            'base_healing_mod_bonus' => $item->base_healing_mod_bonus,
            'base_ac_mod_bonus' => $item->base_ac_mod_bonus,
            'fight_time_out_mod_bonus' => $item->getTotalFightTimeOutMod(),
            'move_time_out_mod_bonus' => $item->move_time_out_mod_bonus,
            'damages_kingdoms' => $item->damages_kingdoms,
            'kingdom_damage' => $item->kingdom_damage,
            'lasts_for' => $item->lasts_for,
            'stat_increase' => $item->stat_increase,
            'increase_stat_by' => $item->increase_stat_by,
            'affects_skills' => GameSkill::where('type', $item->affects_skill_type)->pluck('name')->toArray(),
            'can_resurrect' => $item->can_resurrect,
            'resurrection_chance' => $item->resurrection_chance,
            'spell_evasion' => $item->spell_evasion,
            'healing_reduction' => $item->healing_reduction,
            'affix_damage_reduction' => $item->affix_damage_reduction,
            'increase_skill_bonus_by' => $item->increase_skill_bonus_by,
            'increase_skill_training_bonus_by' => $item->increase_skill_training_bonus_by,
            'is_unique' => $this->isUnique($item),
            'min_cost' => $item->cost,
            'holy_level' => $item->holy_level,
            'holy_stacks' => $item->holy_stacks,
            'applied_stacks' => $item->appliedHolyStacks,
            'holy_stack_devouring_darkness' => $item->holy_stack_devouring_darkness,
            'holy_stack_stat_bonus' => $item->holy_stack_stat_bonus,
            'holy_stacks_applied' => $item->holy_stacks_applied,
            'ambush_chance' => $item->ambush_chance,
            'ambush_resistance_chance' => $item->ambush_resistance,
            'counter_chance' => $item->counter_chance,
            'counter_resistance_chance' => $item->counter_resistance,
            'devouring_light' => $item->devouring_light,
            'devouring_darkness' => $item->devouring_darkness,
            'ambush_resistance' => $item->ambush_resistance,
            'counter_resistance' => $item->counter_resistance,
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
            'xp_bonus' => $item->xp_bonus,
            'ignores_caps' => $item->ignores_caps,
            'sockets' => $item->sockets,
            'socket_amount' => $item->socket_count,
            'item_atonements' => $this->getElementAtonement($item),
        ];
    }
}
