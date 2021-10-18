<?php

namespace App\Flare\Services;

use Illuminate\Support\Facades\Cache;
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

        foreach (GameMap::all() as $gameMap) {
            $monsters =  new Collection(
                Monster::where('published', true)
                    ->where('is_celestial_entity', false)
                    ->where('game_map_id', $gameMap->id)
                    ->orderBy('max_level', 'asc')->get(),
                $this->monster
            );

            $monstersCache[$gameMap->name] = $this->manager->createData($monsters)->toArray();
        }

        Cache::put('monsters', $monstersCache);
    }
}