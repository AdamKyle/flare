<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\ClassAttackValue;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Cache;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Character;

class CharacterAttackTransformer extends TransformerAbstract {


    /**
     * creates response data for character attack data.
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {

        $gameClass = GameClass::find($character->game_class_id);

        return [
            'id'                          => $character->id,
            'name'                        => $character->name,
            'class'                       => $gameClass->name,
            'dex'                         => $this->fetchStats($character, 'dex_modded'),
            'dur'                         => $this->fetchStats($character, 'dur_modded'),
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
            'extra_action_chance'         => (new ClassAttackValue($character))->buildAttackData(),
            'is_alchemy_locked'           => $this->isAlchemyLocked($character),
            'stat_affixes'                => $this->fetchStatAffixes($character),
            'skill_reduction'             => $this->fetchStats($character, 'skill_reduction'),
            'resistance_reduction'        => $this->fetchStats($character, 'resistance_reduction'),
            'attack_types'                => $this->fetchAttackTypes($character),
            'disable_pop_overs'           => $character->user->disable_attack_type_popover,
            'is_attack_automation_locked' => $character->is_attack_automation_locked,
            'can_auto_battle'             => $character->user->can_auto_battle,
            'can_attack_again_at'         => $character->can_attack_again_at,
        ];
    }

    public function fetchAttackTypes(Character $character): array {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return [];
        }

        return $cache['attack_types'];
    }

    public function fetchStats(Character $character, string $stat): mixed {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return 0.0;
        }

        return $cache['character_data'][$stat];
    }

    public function fetchStatAffixes(Character $character): array {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return [];
        }

        return $cache['stat_affixes'];
    }

    public function fetchSkills(Character $character): array {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return [];
        }

        return $cache['skills'];
    }

    private function isAlchemyLocked(Character $character) {
        $alchemy = GameSkill::where('type', SkillTypeValue::ALCHEMY)->first();

        if (is_null($alchemy)) {
            return true;
        }

        $skill = Skill::where('game_skill_id', $alchemy->id)->where('character_id', $character->id)->first();

        if (!is_null($skill)) {
            return $skill->is_locked;
        }

        return true;
    }
}
