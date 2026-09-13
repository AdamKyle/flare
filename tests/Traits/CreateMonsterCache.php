<?php

namespace Tests\Traits;

use App\Flare\Models\GameMap;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Support\Facades\Cache;

trait CreateMonsterCache
{
    /**
     * Creates an empty per-Game-Map Monster cache entry for each commonly used test Map name,
     * reusing an already-created Game Map of that name when the test already made one.
     *
     * The cache is empty and should not be filled for tests.
     */
    public function createMonsterCache()
    {
        collect(['Surface', 'Labyrinth', 'Dungeons', 'Shadow Plane'])->each(function (string $name): void {
            $gameMap = GameMap::where('name', $name)->first() ?? GameMap::factory()->create(['name' => $name]);

            Cache::put(MonsterCacheKey::forGameMap($gameMap->id), []);
        });
    }
}
