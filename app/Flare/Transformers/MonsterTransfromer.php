<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Monster;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class MonsterTransfromer extends TransformerAbstract {

    use SkillsTransformerTrait;

    public function transform(Monster $monster) {

        return [
            'id'           => $monster->id,
            'name'         => $monster->name,
            'damage_stat'  => $monster->damage_stat,
            'str'          => $monster->str,
            'dur'          => $monster->dur,
            'dex'          => $monster->dex,
            'chr'          => $monster->chr,
            'int'          => $monster->int,
            'ac'           => $monster->ac,
            'health_range' => $monster->health_range,
            'attack_range' => $monster->attack_range,
            'skills'       => $this->fetchSkills($monster->skills),
            'base_stat'    => $monster->{$monster->damage_stat},
        ];
    }
}
