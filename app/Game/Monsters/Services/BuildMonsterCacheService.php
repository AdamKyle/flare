<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\Monsters\Transformers\MonsterTransformer;
use Facades\App\Game\Maps\Calculations\LocationBasedEnemyDropChanceBonus;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Psr\SimpleCache\InvalidArgumentException;

class BuildMonsterCacheService
{
    public function __construct(private readonly Manager $manager, private MonsterTransformer $monster) {}

    /**
     * Builds monster cache.
     *
     * @throws InvalidArgumentException
     */
    public function buildCache(): void
    {
        $monstersCache = [];

        Cache::delete('monsters');

        $this->monster = $this->monster->setIsMonsterSpecial(true);

        foreach (GameMap::all() as $gameMap) {
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

        $monstersCache = $monstersCache + $this->manageMonsters($monstersCache);

        Cache::put('monsters', $monstersCache);
    }

    /**
     * Builds raid monster cache.
     *
     * @throws InvalidArgumentException
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

        $monstersCache = $monstersCache + $this->manageMonsters($monstersCache);

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

            $locationFlat = is_null($location->enemy_strength_increase) ? 0.0 : $location->enemy_strength_increase;
            $locationPercent = is_null($location->enemy_strength_increase) ? 0.0 : LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent($location->enemy_strength_increase) / 100.0;

            $mapDropBonus = $location->map ? ($location->map->drop_chance_bonus ?? 0.0) : 0.0;
            $extraDropChance = $mapDropBonus + $locationPercent;

            $transformer = (clone $this->monster)
                ->setIsMonsterSpecial(true)
                ->withLocationFlat($locationFlat)
                ->withLocationPercent($locationPercent)
                ->withExtraDropChance($extraDropChance);

            $monsters = new Collection(
                Monster::where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->where('only_for_location_type', $location->type)
                    ->get(),
                $transformer
            );

            $monsters = $this->manager->createData($monsters)->toArray();

            if (! empty($monsters)) {
                $cache['location-type-'.$location->type] = $monsters;
            }
        }

        Cache::put('special-location-monsters', $cache);
    }

    /**
     * Builds celestial cache.
     *
     * @throws InvalidArgumentException
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

    protected function createMonstersForEventMaps(Collection $monsters): array
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

    /**
     * Get monsters for special locations.
     */
    protected function manageMonsters(array $monstersCache): array
    {
        foreach (Location::whereNotNull('enemy_strength_increase')->get() as $location) {
            $monsters = Monster::where('is_celestial_entity', false)
                ->where('game_map_id', $location->game_map_id)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->get();

            $monsters = $this->transformMonsterForLocation(
                $monsters,
                $location->enemy_strength_increase,
                LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent($location->enemy_strength_increase),
            );

            $monsterTransformer = $this->monster->setIsMonsterSpecial(true);

            $monsters = new Collection($monsters, $monsterTransformer);

            $monstersCache[$location->name] = $this->manager->createData($monsters)->toArray();
        }

        return $monstersCache;
    }

    /**
     * Transform monsters for special location.
     */
    protected function transformMonsterForLocation(DBCollection $monsters, int $increaseStatsBy, float $increasePercentageBy): IlluminateCollection
    {
        return $monsters->transform(function ($monster) use ($increaseStatsBy, $increasePercentageBy) {
            $monster->str += $increaseStatsBy;
            $monster->dex += $increaseStatsBy;
            $monster->agi += $increaseStatsBy;
            $monster->dur += $increaseStatsBy;
            $monster->chr += $increaseStatsBy;
            $monster->int += $increaseStatsBy;
            $monster->ac += $increaseStatsBy;
            $monster->health_range = $this->createNewHealthRange($monster, $increaseStatsBy);
            $monster->attack_range = $this->createNewAttackRange($monster, $increaseStatsBy);
            $monster->spell_evasion += $increasePercentageBy;
            $monster->artifact_annulment += $increasePercentageBy;
            $monster->affix_resistance += $increasePercentageBy;
            $monster->healing_percentage += $increasePercentageBy;
            $monster->entrancing_chance += $increasePercentageBy;
            $monster->devouring_light_chance += $increasePercentageBy;
            $monster->devouring_darkness_chance += $increasePercentageBy;
            $monster->accuracy += $increasePercentageBy;
            $monster->casting_accuracy += $increasePercentageBy;
            $monster->dodge += $increasePercentageBy;
            $monster->criticality += $increasePercentageBy;

            return $monster;
        });
    }

    protected function createNewHealthRange(Monster $monster, int $increaseStatsBy): string
    {
        $monsterHealthRangeParts = explode('-', $monster->health_range);

        $minHealth = intval($monsterHealthRangeParts[0]) + $increaseStatsBy;
        $maxHealth = intval($monsterHealthRangeParts[1]) + $increaseStatsBy;

        return $minHealth.'-'.$maxHealth;
    }

    protected function createNewAttackRange(Monster $monster, int $increaseStatsBy): string
    {
        $monsterAttackParts = explode('-', $monster->attack_range);

        $minAttack = intval($monsterAttackParts[0]) + $increaseStatsBy;
        $maxAttack = intval($monsterAttackParts[1]) + $increaseStatsBy;

        return $minAttack.'-'.$maxAttack;
    }
}
