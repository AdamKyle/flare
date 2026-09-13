<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\GameMap;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;

class CreateMonsterCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:monster-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates monsters to fight';

    /**
     * Execute the console command, reporting progress per Game Map for the
     * canonical per-Map Monster cache before rebuilding the remaining caches.
     *
     * @param BuildMonsterCacheService $buildMonsterCacheService
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function handle(BuildMonsterCacheService $buildMonsterCacheService): void
    {
        $gameMaps = GameMap::all();

        $this->output->progressStart($gameMaps->count());

        foreach ($gameMaps as $gameMap) {
            $buildMonsterCacheService->rebuildMapCache($gameMap);

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $buildMonsterCacheService->buildLocationCache();
        $buildMonsterCacheService->buildWeeklyFightCache();
        $buildMonsterCacheService->buildRaidCache();
        $buildMonsterCacheService->buildCelestialCache();
        $buildMonsterCacheService->finalizeCanonicalCache();

        $this->info('Monster cache regenerated.');
    }
}
