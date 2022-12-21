<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Monster;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class RankMonsterTransformer extends TransformerAbstract {

    use SkillsTransformerTrait;

    /**
     * @var int $statAmount
     */
    private int $statAmount;

    /**
     * @param int $statAmount
     * @return RankMonsterTransformer
     */
    public function setStat(int $statAmount): RankMonsterTransformer {
        $this->statAmount = $statAmount;

        return $this;
    }

    /**
     * Fetches the monster response data
     *
     * @param Monster $monster
     * @return array
     */
    public function transform(Monster $monster) {

        return [
            'id'                        => $monster->id,
            'name'                      => $monster->name,
            'map_name'                  => $monster->gameMap->name,
            'damage_stat'               => $monster->damage_stat,
            'str'                       => $this->statAmount,
            'dur'                       => $this->statAmount,
            'dex'                       => $this->statAmount,
            'chr'                       => $this->statAmount,
            'int'                       => $this->statAmount,
            'agi'                       => $this->statAmount,
            'focus'                     => $this->statAmount,
            'to_hit_base'               => $monster->dex,
            'ac'                        => $this->statAmount / 2,
            'health_range'              => $this->statAmount . '-' . ($this->statAmount + ($this->statAmount / 2)),
            'attack_range'              => $this->statAmount . '-' . ($this->statAmount + ($this->statAmount / 2)),
            'accuracy'                  => 1,
            'dodge'                     => 1,
            'casting_accuracy'          => 1,
            'criticality'               => 1,
            'base_stat'                 => $this->statAmount / 2,
            'max_level'                 => 9999,
            'has_damage_spells'         => true,
            'spell_damage'              => $this->statAmount,
            'spell_evasion'             => 1.5,
            'affix_resistance'          => 1.5,
            'max_affix_damage'          => $this->statAmount,
            'max_healing'               => 1,
            'entrancing_chance'         => 1.5,
            'devouring_light_chance'    => 1,
            'devouring_darkness_chance' => 1,
            'ambush_chance'             => 1,
            'ambush_resistance_chance'  => 1,
            'counter_chance'            => 1,
            'counter_resistance_chance' => 1,
            'increases_damage_by'       => 0,
            'is_special'                => false,
        ];
    }
}
