<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Cache;

trait CreateMonsterCache
{
    /**
     * Creates a cache of monsters.
     *
     * The cache is empty and should not be filled for tests.
     */
    public function createMonsterCache()
    {
        Cache::put('monsters', [
            'Surface' => [],
            'Labyrinth' => [],
            'Dungeons' => [],
            'Shadow Plane' => [],
        ]);
    }
}
