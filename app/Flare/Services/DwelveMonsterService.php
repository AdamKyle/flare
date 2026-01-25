<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\DwelveExploration;

class DwelveMonsterService {

    const MONSTER_STATS = [
        'str',
        'dex',
        'agi',
        'dur',
        'chr',
        'int',
        'ac',
    ];

    const PERCENTAGE_STATS = [
        'spell_evasion',
        'affix_resistance',
        'healing_percentage',
        'entrancing_chance',
        'devouring_light_chance',
        'devouring_darkness_chance',
        'accuracy',
        'casting_accuracy',
        'dodge',
        'criticality',
    ];

    public function createMonster(array $monster, Character $character): array {

        $dwelveExploration = DwelveExploration::where('character_id', $character->id)->whereNull('completed_at')->first();

        if (is_null($dwelveExploration)) {
            return $monster;
        }

        if ($dwelveExploration->increase_enemy_strength <= 0) {
            return $monster;
        }

        $increaseAmount = $dwelveExploration->increase_enemy_strength;

        if (!isset($monster['damage_stat'])) {
            dump('Monster stat damage stat is not set, what is monster:');
            dd($monster);
        }

        $monsterDamageStat = $monster[$monster['damage_stat']] * $increaseAmount;

        $monster = $this->increaseMonsterStats($monster, $monsterDamageStat);

        return $this->increasePercentageAttributes($monster, $increaseAmount);
    }

    private function increaseMonsterStats(array $monster, int $increaseStatsBy): array {

        foreach (self::MONSTER_STATS as $stat) {
            $monster[$stat] += $increaseStatsBy;
        }

        if (!isset($monster['attack_range'])) {
            dump('Monster does not have damage range?');
            dd($monster);
        }

        $monster['health_range'] = $this->setNewRange($monster['health_range'], $increaseStatsBy);
        $monster['attack_range'] = $this->setNewRange($monster['attack_range'], $increaseStatsBy);

        return $monster;
    }

    private function increasePercentageAttributes(array $monster, float $increaseBy): array {
        foreach (self::PERCENTAGE_STATS as $stat) {
            $monsterPercentStatAmount = min(($monster[$stat] + $increaseBy), 1.25);

            $monster[$stat] = $monsterPercentStatAmount;
        }

        return $monster;
    }

    private function setNewRange(string $range, int $increaseBy): string {

        $rangeRaw = explode('-', $range);

        $min = intval($rangeRaw[0]) + $increaseBy;
        $max = intval($rangeRaw[1]) + $increaseBy;

        return $min . '-' . $max;
    }
}
