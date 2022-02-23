<?php

namespace App\Flare\Services;

use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection as IlluminateCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Transformers\MonsterTransfromer;

class BuildMonsterCacheService {

    private $manager;

    private $monster;

    public function __construct(Manager $manager, MonsterTransfromer $monster) {
        $this->manager            = $manager;
        $this->monster            = $monster;
    }

    public function buildCache() {
        $monstersCache = [];

        Cache::delete('monsters');

        foreach (GameMap::all() as $gameMap) {
            $monsters =  new Collection(
                Monster::where('published', true)
                    ->where('is_celestial_entity', false)
                    ->where('game_map_id', $gameMap->id)
                    ->get(),
                $this->monster
            );

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        foreach (Location::whereNotNull('enemy_strength_type')->get() as $location) {
            $monsters = Monster::where('published', true)
                               ->where('is_celestial_entity', false)
                               ->where('game_map_id', $location->game_map_id)
                               ->get();

            switch ($location->enemy_strength_type) {
                case LocationEffectValue::INCREASE_STATS_BY_HUNDRED_THOUSAND:
                    $monsters = $this->transformMonsterForLocation($monsters, LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type), LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type));
                    break;
                case LocationEffectValue::INCREASE_STATS_BY_ONE_MILLION:
                    $monsters = $this->transformMonsterForLocation($monsters, LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type), LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type));
                    break;
                case LocationEffectValue::INCREASE_STATS_BY_TEN_MILLION:
                    $monsters = $this->transformMonsterForLocation($monsters, LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type), LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type));
                    break;
                case LocationEffectValue::INCREASE_STATS_BY_HUNDRED_MILLION:
                    $monsters = $this->transformMonsterForLocation($monsters, LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type), LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type));
                    break;
                case LocationEffectValue::INCREASE_STATS_BY_ONE_BILLION:
                    $monsters = $this->transformMonsterForLocation($monsters, LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type), LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type));
                    break;
                default:
                    break;
            }

            $monsters = new Collection($monsters, $this->monster);

            $monstersCache[$location->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put('monsters', $monstersCache);
    }

    public function fetchMonsterCache(string $planeName) {
        $cache = Cache::get('monsters');

        if (is_null($cache)) {
            $this->buildCache();
        }

        return Cache::get('monsters')[$planeName];
    }

    public function fetchMonsterFromCache(string $planeName, string $monsterName) {
        $cache = Cache::get('monsters');

        if (is_null($cache)) {
            $this->buildCache();
        }

        return collect(Cache::get('monsters')[$planeName])->where('name', $monsterName)->first();
    }

    protected function transformMonsterForLocation(DBCollection $monsters, int $increaseStatsBy, float $increasePercentageBy): IlluminateCollection {
        return $monsters->transform(function($monster) use ($increaseStatsBy, $increasePercentageBy) {
            $monster->str                       += $increaseStatsBy;
            $monster->dex                       += $increaseStatsBy;
            $monster->agi                       += $increaseStatsBy;
            $monster->dur                       += $increaseStatsBy;
            $monster->chr                       += $increaseStatsBy;
            $monster->int                       += $increaseStatsBy;
            $monster->ac                        += $increaseStatsBy;
            $monster->spell_evasion             += $increasePercentageBy;
            $monster->artifact_annulment        += $increasePercentageBy;
            $monster->affix_resistance          += $increasePercentageBy;
            $monster->healing_percentage        += $increasePercentageBy;
            $monster->entrancing_chance         += $increasePercentageBy;
            $monster->devouring_light_chance    += $increasePercentageBy;
            $monster->devouring_darkness_chance += $increasePercentageBy;
            $monster->accuracy                  += $increasePercentageBy;
            $monster->casting_accuracy          += $increasePercentageBy;
            $monster->dodge                     += $increasePercentageBy;
            $monster->criticality               += $increasePercentageBy;

            return $monster;
        });
    }
}
