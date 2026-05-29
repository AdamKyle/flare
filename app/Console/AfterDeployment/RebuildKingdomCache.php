<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Game\Core\Traits\KingdomCache;

class RebuildKingdomCache extends Command
{

    use KingdomCache;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:kingdom-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild kingdom caches';

    public function handle(): void
    {
        $this->rebuildPlayerKingdomCaches();
        $this->rebuildEnemyKingdomCaches();
    }

    /**
     * Rebuild all player kingdom caches.
     */
    private function rebuildPlayerKingdomCaches(): void
    {
        Character::chunkById(100, function ($characters) {
            foreach ($characters as $character) {
                $this->rebuildCharacterKingdomCache($character);
            }
        });
    }

    /**
     * Rebuild all enemy kingdom caches.
     */
    private function rebuildEnemyKingdomCaches(): void
    {
        foreach (GameMap::all() as $gameMap) {
            $plane = $gameMap->name;

            Cache::delete('enemy-kingdoms-'.$plane);
        }
    }
}
