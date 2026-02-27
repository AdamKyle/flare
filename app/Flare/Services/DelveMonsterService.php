<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;

class DelveMonsterService
{
    const MONSTER_STATS = [
        'str',
        'dex',
        'agi',
        'dur',
        'chr',
        'int',
        'ac',
        'spell_damage',
        'max_affix_damage',
    ];

    const PERCENTAGE_STATS = [
        'spell_evasion',
        'affix_resistance',
        'max_healing',
        'entrancing_chance',
        'devouring_light_chance',
        'devouring_darkness_chance',
        'accuracy',
        'casting_accuracy',
        'dodge',
        'criticality',
        'ambush_chance',
        'counter_chance',
        'counter_resistance_chance',
        'ambush_resistance_chance',
        'increases_damage_by',
        'life_stealing_resistance',
    ];

    public function createMonster(array $monster, Character $character): array
    {

        $delveExploration = DelveExploration::where('character_id', $character->id)->whereNull('completed_at')->first();

        if (is_null($delveExploration)) {
            return $monster;
        }

        if ($delveExploration->increase_enemy_strength <= 0) {
            return $monster;
        }

        $increaseAmount = $delveExploration->increase_enemy_strength;

        $monsterDamageStat = $monster[$monster['damage_stat']] * $increaseAmount;

        $monster = $this->increaseMonsterStats($monster, $monsterDamageStat);

        $monster['elemental_atonement'] = [
            'fire' => 0,
            'ice' => 0,
            'water' => 0,
        ];

        return $this->increasePercentageAttributes($monster, $increaseAmount);
    }

    private function increaseMonsterStats(array $monster, int $increaseStatsBy): array
    {

        foreach (self::MONSTER_STATS as $stat) {
            $monster[$stat] += $increaseStatsBy;
        }

        $monster['health_range'] = $this->setNewRange($monster['health_range'], $increaseStatsBy);
        $monster['attack_range'] = $this->setNewRange($monster['attack_range'], $increaseStatsBy);

        return $monster;
    }

    private function increasePercentageAttributes(array $monster, float $increaseBy): array
    {
        foreach (self::PERCENTAGE_STATS as $stat) {
            $monsterPercentStatAmount = min(($monster[$stat] + $increaseBy), 1.25);

            $monster[$stat] = $monsterPercentStatAmount;
        }

        return $monster;
    }

    private function setNewRange(string $range, int $increaseBy): string
    {

        $rangeRaw = explode('-', $range);

        $min = intval($rangeRaw[0]) + $increaseBy;
        $max = intval($rangeRaw[1]) + $increaseBy;

        return $min.'-'.$max;
    }
}
