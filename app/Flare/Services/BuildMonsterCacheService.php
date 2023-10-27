<?php

namespace App\Flare\Services;

use App\Flare\Models\RankFight;
use App\Flare\Transformers\RankMonsterTransformer;
use App\Flare\Values\MapNameValue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection as IlluminateCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Transformers\MonsterTransformer;
use League\Fractal\Resource\Item;

class BuildMonsterCacheService {

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var MonsterTransformer $monster
     */
    private MonsterTransformer $monster;

    /**
     * @var RankMonsterTransformer $rankMonsterTransformer
     */
    private RankMonsterTransformer $rankMonsterTransformer;

    /**
     * @param Manager $manager
     * @param MonsterTransformer $monster
     * @param RankMonsterTransformer $rankMonsterTransformer
     */
    public function __construct(Manager $manager, MonsterTransformer $monster, RankMonsterTransformer $rankMonsterTransformer) {
        $this->manager                = $manager;
        $this->monster                = $monster;
        $this->rankMonsterTransformer = $rankMonsterTransformer;
    }

    /**
     * Builds monster cache.
     *
     * @return void
     */
    public function buildCache(): void {
        $monstersCache = [];

        Cache::delete('monsters');

        foreach (GameMap::all() as $gameMap) {
            $monsters =  new Collection(
                Monster::where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->where('game_map_id', $gameMap->id)
                    ->get(),
                $this->monster
            );


            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        $monstersCache = $monstersCache + $this->manageMonsters($monstersCache);

        Cache::put('monsters', $monstersCache);
    }

    /**
     * Builds raid monster cache.
     *
     * @return void
     */
    public function buildRaidCache(): void {
        $monstersCache = [];

        Cache::delete('raid-monsters');

        foreach (GameMap::all() as $gameMap) {

            $raidCritters = Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', true)
                ->where('is_raid_boss', false)
                ->where('game_map_id', $gameMap->id)
                ->get();

            $raidBosses = Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', true)
                ->where('game_map_id', $gameMap->id)
                ->get();


            $monsters =  new Collection(
                $raidCritters->merge($raidBosses),
                $this->monster
            );


            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        $monstersCache = $monstersCache + $this->manageMonsters($monstersCache);

        Cache::put('raid-monsters', $monstersCache);
    }

    /**
     * Builds celestial cache.
     *
     * @return void
     */
    public function buildCelesetialCache(): void {
        $monstersCache = [];

        Cache::delete('celestials');

        foreach (GameMap::all() as $gameMap) {
            $monsters =  new Collection(
                Monster::where('is_celestial_entity', true)
                    ->where('game_map_id', $gameMap->id)
                    ->get(),
                $this->monster
            );

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put('celestials', $monstersCache);
    }

    /**
     * @return void
     */
    public function createRankMonsters(): void {
        $rankCache = [];

        Cache::delete('rank-monsters');

        $currentRank       = RankFight::first();

        if (is_null($currentRank)) {
            Artisan::call('update:rank-fights');

            return;
        }

        $monsters          = Monster::where('game_map_id', GameMap::where('name', MapNameValue::SURFACE)->first()->id)
            ->where('is_celestial_entity', false)
            ->get();
        $iteration         = $monsters->count();
        $baseAmount        = 100000;
        $maxAmount         = 1000000;

        for ($i = 1; $i <= $currentRank->current_rank; $i++) {

            $increments = ($maxAmount - $baseAmount) / $iteration;

            foreach ($monsters as $monster) {

                if ($baseAmount > 500000000000) {
                    $baseAmount = 500000000000;
                }

                $transformer = $this->rankMonsterTransformer->setStat($baseAmount);

                $monster = new Item($monster, $transformer);

                $rankCache[$i][] = $this->manager->createData($monster)->toArray();

                $baseAmount += $increments;
            }

            $maxAmount += $maxAmount;
        }

        Cache::put('rank-monsters', $rankCache);
    }

    /**
     * Fetch monsters from cache.
     *
     * - Will build the cache if none exists.
     *
     * @param string $planeName
     * @return array
     */
    public function fetchMonsterCache(string $planeName): array {
        $cache = Cache::get('monsters');

        if (is_null($cache)) {
            $this->buildCache();
        }

        return Cache::get('monsters')[$planeName];
    }

    /**
     * Fetch monster from cache.
     *
     * - Will build the cache if none exists.
     *
     * @param string $planeName
     * @param string $monsterName
     * @return IlluminateCollection
     */
    public function fetchMonsterFromCache(string $planeName, string $monsterName): IlluminateCollection {
        $cache = Cache::get('monsters');

        if (is_null($cache)) {
            $this->buildCache();
        }

        return collect(Cache::get('monsters')[$planeName])->where('name', $monsterName)->first();
    }

    /**
     * Fetch celestial from cache.
     *
     * - Will build the cache if none exists.
     *
     * @param string $planeName
     * @param string $monsterName
     * @return IlluminateCollection
     */
    public function fetchCelestialsFromCache(string $planeName, string $monsterName): IlluminateCollection {
        $cache = Cache::get('celestials');

        if (is_null($cache)) {
            $this->buildCelesetialCache();
        }

        return collect(Cache::get('celestials')[$planeName])->where('name', $monsterName)->first();
    }

    /**
     * Get monsters for special locations.
     *
     * @param array $monstersCache
     * @return array
     */
    protected function manageMonsters(array $monstersCache): array {
        foreach (Location::whereNotNull('enemy_strength_type')->get() as $location) {
            $monsters = Monster::where('is_celestial_entity', false)
                ->where('game_map_id', $location->game_map_id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->get();

            $monsters = $this->transformMonsterForLocation(
                $monsters,
                LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type),
                LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type)
            );

            $monsterTransformer = $this->monster->setIsMonsterSpecial(true);

            $monsters = new Collection($monsters, $monsterTransformer);

            $monstersCache[$location->name] = $this->manager->createData($monsters)->toArray();
        }

        return $monstersCache;
    }

    /**
     * Transform monsters for special location.
     *
     * @param DBCollection $monsters
     * @param int $increaseStatsBy
     * @param float $increasePercentageBy
     * @return IlluminateCollection
     */
    protected function transformMonsterForLocation(DBCollection $monsters, int $increaseStatsBy, float $increasePercentageBy): IlluminateCollection {
        return $monsters->transform(function ($monster) use ($increaseStatsBy, $increasePercentageBy) {
            $monster->str                       += $increaseStatsBy;
            $monster->dex                       += $increaseStatsBy;
            $monster->agi                       += $increaseStatsBy;
            $monster->dur                       += $increaseStatsBy;
            $monster->chr                       += $increaseStatsBy;
            $monster->int                       += $increaseStatsBy;
            $monster->ac                        += $increaseStatsBy;
            $monster->health_range              = $this->createNewHealthRange($monster, $increaseStatsBy);
            $monster->attack_range              = $this->createNewAttackRange($monster, $increaseStatsBy);
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

    protected function createNewHealthRange(Monster $monster, int $increaseStatsBy): string {
        $monsterHealthRangeParts = explode('-', $monster->health_range);

        $minHealth = intval($monsterHealthRangeParts[0]) + $increaseStatsBy;
        $maxHealth = intval($monsterHealthRangeParts[1]) + $increaseStatsBy;

        return $minHealth . '-' . $maxHealth;
    }

    protected function createNewAttackRange(Monster $monster, int $increaseStatsBy): string {
        $monsterAttackParts = explode('-', $monster->attack_range);

        $minAttack = intval($monsterAttackParts[0]) + $increaseStatsBy;
        $maxAttack = intval($monsterAttackParts[1]) + $increaseStatsBy;

        return $minAttack . '-' . $maxAttack;
    }
}
