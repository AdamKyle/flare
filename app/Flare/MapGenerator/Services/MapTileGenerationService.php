<?php

namespace App\Flare\MapGenerator\Services;

use App\Flare\Models\GameMap;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MapTileGenerationService
{
    public function __construct(private readonly ImageTilerService $imageTilerService) {}

    /**
     * Break the given game map's image into tiles and store the resulting tile map on it.
     */
    public function tile(GameMap $gameMap): void
    {
        $folderName = Str::lower($gameMap->name).'-pieces';

        if (Storage::disk('maps')->exists($folderName)) {
            return;
        }

        $imagePath = Storage::disk('maps')->path($gameMap->path);

        $tileMap = $this->imageTilerService->breakIntoTiles($imagePath, $folderName);

        $gameMap->update([
            'tile_map' => $tileMap,
        ]);
    }
}
