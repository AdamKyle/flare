<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\Monsters\Transformers\MonsterTransformer;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class BuildMonsterCacheService
{
    public function __construct(private readonly Manager $manager, private MonsterTransformer $monster) {}

    /**
     * Builds monster cache.
     */
    public function buildCache(): void
    {
        $monstersCache = [];

        Cache::delete('monsters');

        $this->monster = $this->monster->setIsMonsterSpecial(true);

        foreach (GameMap::all() as $gameMap) {

            $enemyIncrease = $gameMap->enemy_stat_bonus ?? 0.0;
            $enemyDropBonus = $gameMap->drop_chance_bonus ?? 0.0;

            $this->monster->withEnemyIncrease($enemyIncrease)
                ->withDropChanceIncrease($enemyDropBonus);

            $monsters = new Collection(
                Monster::where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->whereNull('only_for_location_type')
                    ->where('game_map_id', $gameMap->id)
                    ->get(),
                $this->monster
            );

            if (! is_null($gameMap->only_during_event_type)) {
                $monstersCache[$gameMap->name] = $this->createMonstersForEventMaps($monsters);

                continue;
            }

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put('monsters', $monstersCache);
    }

    /**
     * Builds raid monster cache.
     */
    public function buildRaidCache(): void
    {
        $monstersCache = [];

        Cache::delete('raid-monsters');

        foreach (GameMap::all() as $gameMap) {

            $raidCritters = Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', true)
                ->where('is_raid_boss', false)
                ->where('game_map_id', $gameMap->id)
                ->whereNull('only_for_location_type')
                ->get();

            $raidBosses = Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', true)
                ->where('game_map_id', $gameMap->id)
                ->whereNull('only_for_location_type')
                ->get();

            $monsters = new Collection(
                $raidBosses->merge($raidCritters),
                $this->monster
            );

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put('raid-monsters', $monstersCache);
    }

    /**
     * Build special location monsters
     */
    public function buildSpecialLocationMonsterList(): void
    {
        $locations = Location::whereNotNull('type')->get();

        $cache = [];

        foreach ($locations as $location) {

            $enemyIncrease = $location->map->enemy_stat_bonus ?? 0.0;
            $dropChanceIncrease = $location->map->drop_chance_bonus ?? 0.0;

            $transformer = $this->monster
                ->setIsMonsterSpecial(true)
                ->withEnemyIncrease($enemyIncrease)
                ->withDropChanceIncrease($dropChanceIncrease);

            $monsters = new Collection(
                Monster::where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->where('only_for_location_type', $location->type)
                    ->get(),
                $transformer
            );

            $monsters = $this->manager->createData($monsters)->toArray();

            $cache['location-type-'.$location->type] = $monsters;
        }

        Cache::put('special-location-monsters', $cache);
    }

    /**
     * Builds celestial cache.
     */
    public function buildCelesetialCache(): void
    {
        $monstersCache = [];

        Cache::delete('celestials');

        $this->monster = $this->monster->setIsMonsterSpecial(true);

        foreach (GameMap::all() as $gameMap) {
            $monsters = new Collection(
                Monster::where('is_celestial_entity', true)
                    ->where('game_map_id', $gameMap->id)
                    ->whereNull('only_for_location_type')
                    ->get(),
                $this->monster
            );

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put('celestials', $monstersCache);
    }

    public function createNewHealthRange(Monster $monster, int $increaseStatsBy): string
    {
        $monsterHealthRangeParts = explode('-', $monster->health_range);

        $minHealth = intval($monsterHealthRangeParts[0]) + $increaseStatsBy;
        $maxHealth = intval($monsterHealthRangeParts[1]) + $increaseStatsBy;

        return $minHealth.'-'.$maxHealth;
    }

    public function createNewAttackRange(Monster $monster, int $increaseStatsBy): string
    {
        $monsterAttackParts = explode('-', $monster->attack_range);

        $minAttack = intval($monsterAttackParts[0]) + $increaseStatsBy;
        $maxAttack = intval($monsterAttackParts[1]) + $increaseStatsBy;

        return $minAttack.'-'.$maxAttack;
    }

    private function createMonstersForEventMaps(Collection $monsters): array
    {
        $surface = GameMap::where('default', true)->first();

        $easierMonsters = new Collection(
            Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('game_map_id', $surface->id)
                ->get(),
            $this->monster
        );

        return [
            'regular' => $this->manager->createData($monsters)->toArray(),
            'easier' => $this->manager->createData($easierMonsters)->toArray(),
        ];
    }
}
