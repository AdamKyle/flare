<?php

namespace App\Admin\GameMaps\Jobs;

use App\Admin\GameMaps\Services\GameMapService;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\Models\GameMap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class GenerateGameMapTilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  int  $gameMapId  Persisted Game Map identifier.
     */
    public function __construct(public readonly int $gameMapId) {}

    /**
     * Generate the initial tile set for the persisted Game Map.
     *
     * @param  GameMapService  $gameMapService  Canonical Game Map application service.
     * @param  MapTileGenerationService  $mapTileGenerationService  Map tile generation service.
     * @return void Persists the generated tile set on the Game Map.
     */
    public function handle(
        GameMapService $gameMapService,
        MapTileGenerationService $mapTileGenerationService,
    ): void {
        $gameMap = GameMap::find($this->gameMapId);

        if (is_null($gameMap)) {
            return;
        }

        if (! $gameMapService->processInitialTiles($gameMap, $mapTileGenerationService)) {
            throw new RuntimeException('Initial Game Map tile processing failed.');
        }
    }
}
