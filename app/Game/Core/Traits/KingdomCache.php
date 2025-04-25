<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use Cache;
use Illuminate\Database\Eloquent\Collection;

trait KingdomCache
{
    /**
     * Get the kingdom information from cache.
     *
     * If the  character does not have a cache of kingdoms, we will
     * then create the cache for them, so next time it's easy to fetch.
     */
    public function getKingdoms(Character $character): array
    {
        $kingdoms = Kingdom::select('id', 'x_position', 'y_position', 'color', 'name')
            ->where('character_id', $character->id)
            ->where('game_map_id', $character->map->game_map_id)
            ->get();

        if ($kingdoms->isEmpty()) {
            return [];
        }

        return $this->createKingdomArray($kingdoms);
    }

    /**
     * Rebuild a characters' kingdom Cache.
     */
    public function rebuildCharacterKingdomCache(Character $character)
    {

        foreach (GameMap::all() as $gameMap) {
            $plane = $gameMap->name;

            $kingdoms = Kingdom::select('id', 'x_position', 'y_position', 'color', 'name')
                ->where('character_id', $character->id)
                ->where('game_map_id', $gameMap->id)
                ->get();

            if ($kingdoms->isEmpty()) {
                continue;
            }

            Cache::put('character-kingdoms-'.$plane.'-'.$character->id, $this->createKingdomArray($kingdoms));
        }

    }

    /**
     * Gets the enemy kingdoms cache.
     *
     * @return mixed
     */
    public function getEnemyKingdoms(Character $character, bool $refresh = false)
    {
        $plane = $character->map->gameMap->name;

        if (Cache::has('enemy-kingdoms-'.$plane) && ! $refresh) {
            return Cache::get('enemy-kingdoms-'.$plane);
        } else {
            $kingdoms = Kingdom::select('x_position', 'y_position', 'id', 'color', 'character_id', 'name', 'current_morale', 'game_map_id')
                ->whereNotNull('character_id')
                ->where('game_map_id', $character->map->game_map_id)
                ->get()
                ->transform(function ($kingdom) {
                    $kingdom->character_name = $kingdom->character->name;

                    return $kingdom;
                })->all();

            Cache::put('enemy-kingdoms-'.$plane, $kingdoms);
        }

        return Cache::get('enemy-kingdoms-'.$plane);
    }

    /**
     * Adds a kingdom to the cache.
     *
     * If the cache does not exist, we will create the cache.
     */
    public function addKingdomToCache(Character $character, Kingdom $kingdom): array
    {
        $plane = $character->map->gameMap->name;

        if (Cache::has('character-kingdoms-'.$plane.'-'.$character->id)) {
            $cache = Cache::get('character-kingdoms-'.$plane.'-'.$character->id);

            Cache::put('character-kingdoms-'.$plane.'-'.$character->id, $this->addKingdom($kingdom, $cache));

            return Cache::get('character-kingdoms-'.$plane.'-'.$character->id);
        }

        Cache::put('character-kingdoms-'.$plane.'-'.$character->id, $this->addKingdom($kingdom));

        return Cache::get('character-kingdoms-'.$plane.'-'.$character->id);
    }

    /**
     * Removes a kingdom from the cache.
     *
     * If there is no cache, then we return null.
     */
    public function removeKingdomFromCache(Character $character, Kingdom $kingdom): array
    {
        $plane = $character->map->gameMap->name;

        $cache = Cache::get('character-kingdoms-'.$plane.'-'.$character->id);

        if (is_null($cache)) {
            $cache = $this->getKingdoms($character);
        }

        if (! is_null($cache)) {
            Cache::put('character-kingdoms-'.$plane.'-'.$character->id, $this->removeKingdom($kingdom, $cache));

            return Cache::get('character-kingdoms-'.$plane.'-'.$character->id);
        }

        return $this->getKingdoms($character);
    }

    /**
     * Remove the kingdom from the cache.
     */
    protected function removeKingdom(Kingdom $kingdom, array $cache = []): array
    {
        foreach ($cache as $index => $kingdomData) {
            if ($kingdomData['id'] === $kingdom->id) {
                array_splice($cache, $index, 1);
            }
        }

        return $cache;
    }

    /**
     * Adds a kingdom to the array of cache.
     *
     * If the cache is empty we will set a kingdom to it by pushing
     * the kingdom to the array.
     *
     * @param  array  $cache  | []
     */
    protected function addKingdom(Kingdom $kingdom, array $cache = []): array
    {

        if (! empty($cache)) {
            $key = array_search($kingdom->id, array_column($cache, 'id'));

            if ($key !== false) {
                $cache[$key] = $this->addOrUpdateCache($kingdom);
            } else {
                $cache[] = $this->addOrUpdateCache($kingdom);
            }
        } else {
            $cache[] = $this->addOrUpdateCache($kingdom);
        }

        return $cache;
    }

    /**
     * Returns an array of updated kingdom values.
     */
    protected function addOrUpdateCache(Kingdom $kingdom): array
    {
        return [
            'id' => $kingdom->id,
            'name' => $kingdom->name,
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
            'color' => $kingdom->color,
        ];
    }

    /**
     * Create the kingdom array for the cache.
     */
    protected function createKingdomArray(Collection $kingdoms): array
    {
        $kingdomData = [];

        foreach ($kingdoms as $kingdom) {
            $kingdomData[] = [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'x_position' => $kingdom->x_position,
                'y_position' => $kingdom->y_position,
            ];
        }

        return $kingdomData;
    }
}
