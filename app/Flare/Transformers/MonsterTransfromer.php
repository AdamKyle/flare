<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Monster;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class MonsterTransfromer extends TransformerAbstract {

    use SkillsTransformerTrait;

    /**
     * Fetches the monster response data
     *
     * @param Monster $monster
     */
    public function transform(Monster $monster) {

        return [
            'id'                 => $monster->id,
            'name'               => $monster->name,
            'damage_stat'        => $monster->damage_stat,
            'str'                => $monster->str,
            'dur'                => $monster->dur,
            'dex'                => $monster->dex,
            'chr'                => $monster->chr,
            'int'                => $monster->int,
            'to_hit_base'        => $monster->dex / 10000,
            'ac'                 => $monster->ac,
            'health_range'       => $monster->health_range,
            'attack_range'       => $monster->attack_range,
            'skills'             => $this->fetchSkills($monster->skills),
            'base_stat'          => $monster->{$monster->damage_stat},
            'max_level'          => $monster->max_level,
            'has_damage_spells'  => $monster->can_cast,
            'has_artifacts'      => $monster->can_use_artifacts,
            'artifact_damage'    => $monster->max_artifact_damage,
            'spell_damage'       => $monster->max_spell_damage,
            'artifact_annulment' => $monster->artifact_annulment,
            'spell_evasion'      => $monster->spell_evasion,
        ];
    }
}
