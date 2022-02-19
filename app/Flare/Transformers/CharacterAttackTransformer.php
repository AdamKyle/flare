<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\ClassAttackValue;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Cache;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Character;

class CharacterAttackTransformer extends BaseTransformer {

    /**
     * creates response data for character attack data.
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {

        $gameClass = GameClass::find($character->game_class_id);

        $holyStacks = resolve(HolyStacks::class);

        return [
            'id'                          => $character->id,
            'name'                        => $character->name,
            'class'                       => $gameClass->name,
            'dex_modded'                  => $this->fetchStats($character, 'dex_modded'),
            'dur_modded'                  => $this->fetchStats($character, 'dur_modded'),
            'focus'                       => $this->fetchStats($character, 'focus_modded'),
            'voided_dex'                  => $character->dex,
            'voided_dur'                  => $character->dur,
            'voided_focus'                => $character->focus,
            'to_hit_base'                 => $this->fetchStats($character, 'to_hit_base'),
            'voided_to_hit_base'          => $this->fetchStats($character, 'voided_to_hit_base'),
            'base_stat'                   => $this->fetchStats($character, $gameClass->damage_stat . '_modded'),
            'voided_base_stat'            => $character->{$gameClass->damage_stat},
            'health'                      => $this->fetchStats($character, 'health'),
            'voided_health'               => $character->dur,
            'artifact_annulment'          => $this->fetchStats($character, 'artifact_annulment'),
            'spell_evasion'               => $this->fetchStats($character, 'spell_evasion'),
            'affix_damage_reduction'      => $this->fetchStats($character, 'affix_damage_reduction'),
            'healing_reduction'           => $this->fetchStats($character, 'healing_reduction'),
            'skills'                      => $this->fetchSkills($character),
            'is_dead'                     => $character->is_dead,
            'devouring_light'             => $this->fetchStats($character, 'devouring_light'),
            'devouring_darkness'          => $this->fetchStats($character, 'devouring_darkness'),
            'devouring_light_res'         => $holyStacks->fetchDevouringResistanceBonus($character),
            'devouring_darkness_res'      => $holyStacks->fetchDevouringResistanceBonus($character),
            'extra_action_chance'         => (new ClassAttackValue($character))->buildAttackData(),
            'is_alchemy_locked'           => $this->isAlchemyLocked($character),
            'stat_affixes'                => $this->fetchStatAffixes($character),
            'skill_reduction'             => $this->fetchStats($character, 'skill_reduction'),
            'resistance_reduction'        => $this->fetchStats($character, 'resistance_reduction'),
            'attack_types'                => $this->fetchAttackTypes($character),
            'disable_pop_overs'           => $character->user->disable_attack_type_popover,
            'is_attack_automation_locked' => $character->is_attack_automation_locked,
            'can_attack_again_at'         => $character->can_attack_again_at,
        ];
    }
}
