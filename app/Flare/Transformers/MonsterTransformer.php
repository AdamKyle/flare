<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Monster;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class MonsterTransformer extends TransformerAbstract {

    use SkillsTransformerTrait;

    /**
     * Fetches the monster response data
     *
     * @param Monster $monster
     */
    public function transform(Monster $monster) {

        $shouldIncrease = $this->shouldIncreaseStats($monster);
        $increaseAmount = $monster->gameMap->enemy_stat_bonus;

        return [
            'id'                        => $monster->id,
            'name'                      => $monster->name,
            'map_name'                  => $monster->gameMap->name,
            'damage_stat'               => $monster->damage_stat,
            'str'                       => $shouldIncrease ? $this->increaseValue($monster->str, $increaseAmount) : $monster->str,
            'dur'                       => $shouldIncrease ? $this->increaseValue($monster->dur, $increaseAmount) : $monster->dur,
            'dex'                       => $shouldIncrease ? $this->increaseValue($monster->dex, $increaseAmount) : $monster->dex,
            'chr'                       => $shouldIncrease ? $this->increaseValue($monster->chr, $increaseAmount) : $monster->chr,
            'int'                       => $shouldIncrease ? $this->increaseValue($monster->int, $increaseAmount) : $monster->int,
            'agi'                       => $shouldIncrease ? $this->increaseValue($monster->agi, $increaseAmount) : $monster->agi,
            'focus'                     => $shouldIncrease ? $this->increaseValue($monster->focus, $increaseAmount) : $monster->focus,
            'to_hit_base'               => $shouldIncrease ? $this->increaseValue($monster->dex, $increaseAmount) / 10000 : $monster->dex / 10000,
            'ac'                        => $shouldIncrease ? $this->increaseValue($monster->ac, $increaseAmount) : $monster->ac,
            'health_range'              => $monster->health_range,
            'attack_range'              => $monster->attack_range,
            'accuracy'                  => $shouldIncrease ? $this->increaseValue($monster->accuracy, $increaseAmount) : $monster->accuracy,
            'dodge'                     => $shouldIncrease ? $this->increaseValue($monster->dodge, $increaseAmount) : $monster->dodge,
            'casting_accuracy'          => $shouldIncrease ? $this->increaseValue($monster->casting_accuracy, $increaseAmount) : $monster->casting_accuracy,
            'criticality'               => $shouldIncrease ? $this->increaseValue($monster->criticality, $increaseAmount) : $monster->criticality,
            'base_stat'                 => $shouldIncrease ? $this->increaseValue($monster->{$monster->damage_stat}, $increaseAmount) : $monster->{$monster->damage_stat},
            'max_level'                 => $monster->max_level,
            'has_damage_spells'         => $monster->can_cast,
            'spell_damage'              => $shouldIncrease ? $this->increaseValue($monster->max_spell_damage, $increaseAmount) : $monster->max_spell_damage,
            'spell_evasion'             => $shouldIncrease ? $this->increaseValue($monster->spell_evasion, $increaseAmount) : $monster->spell_evasion,
            'affix_resistance'          => $shouldIncrease ? $this->increaseValue($monster->affix_resistance, $increaseAmount) : $monster->affix_resistance,
            'max_affix_damage'          => $shouldIncrease ? $this->increaseValue($monster->max_affix_damage, $increaseAmount) : $monster->max_affix_damage,
            'max_healing'               => $shouldIncrease ? $this->increaseValue($monster->healing_percentage, $increaseAmount) : $monster->healing_percentage,
            'entrancing_chance'         => $shouldIncrease ? $this->increaseValue($monster->entrancing_chance, $increaseAmount) : $monster->entrancing_chance,
            'devouring_light_chance'    => $shouldIncrease ? $this->increaseValue($monster->devouring_light_chance, $increaseAmount) : $monster->devouring_light_chance,
            'devouring_darkness_chance' => $shouldIncrease ? $this->increaseValue($monster->devouring_darkness_chance, $increaseAmount) : $monster->devouring_darkness_chance,
            'ambush_chance'             => $shouldIncrease ? $this->increaseValue($monster->ambush_chance, $increaseAmount) : $monster->ambush_chance,
            'ambush_resistance_chance'  => $shouldIncrease ? $this->increaseValue($monster->ambush_resistance, $increaseAmount) : $monster->ambush_resistance,
            'counter_chance'            => $shouldIncrease ? $this->increaseValue($monster->counter_chance, $increaseAmount) : $monster->counter_chance,
            'counter_resistance_chance' => $shouldIncrease ? $this->increaseValue($monster->counter_resistance, $increaseAmount) : $monster->counter_resistance,
            'increases_damage_by'       => $monster->gameMap->enemy_stat_bonus,
        ];
    }

    public function increaseValue(int|float $statValue = null, float $increaseBy = null): int|float {
        if (is_null($increaseBy)) {
            return $statValue;
        }

        if ($statValue === 0 || $statValue === 0.0 || is_null($statValue)) {
            return $increaseBy;
        }

        return $statValue + $statValue * $increaseBy;
    }

    public function shouldIncreaseStats(Monster $monster): bool {

        $increase = false;

        switch($monster->gameMap->name) {
            case 'Shadow Plane':
                $increase = true;
                break;
            default:
                $increase = false;
        }

        return $increase;
    }
}
