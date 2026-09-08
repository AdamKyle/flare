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

class ReplaceGameMapTilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $gameMapId,
        public readonly string $previousName,
        public readonly string $previousPath,
    ) {}

    /**
     * Generate and promote replacement tiles for the persisted Game Map.
     */
    public function handle(
        GameMapService $gameMapService,
        MapTileGenerationService $mapTileGenerationService,
    ): void {
        $gameMap = GameMap::find($this->gameMapId);

        if (is_null($gameMap)) {
            return;
        }

        if (! $gameMapService->processReplacementTiles(
            $gameMap,
            $this->previousName,
            $this->previousPath,
            $mapTileGenerationService,
        )) {
            throw new RuntimeException('Replacement Game Map tile processing failed.');
        }
    }
}
