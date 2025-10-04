<?php

namespace App\Flare\MapGenerator\Console\Commands;

use App\Flare\MapGenerator\Services\ImageTilerService;
use App\Flare\Models\GameMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BreakMapsIntoPieces extends Command
{
    protected $signature = 'break:maps-into-pieces';

    protected $description = 'Breaks a large map image into 125x125 pieces';

    public function handle(ImageTilerService $imageTilerService): void
    {
        $gameMaps = GameMap::all();

        foreach ($gameMaps as $gameMap) {

            $folderName = Str::lower($gameMap->name).'-pieces';

            if (Storage::disk('maps')->exists($folderName)) {
                $this->warn("Skipping {$gameMap->name}, pieces already exist.");

                continue;
            }

            $imagePath = Storage::disk('maps')->path($gameMap->path);

            if (! file_exists($imagePath)) {
                $this->error("Image file for {$gameMap->name} not found at: {$imagePath}");

                continue;
            }

            $tileMap = $imageTilerService->breakIntoTiles($imagePath, $folderName);

            $gameMap->update([
                'tile_map' => $tileMap,
            ]);

            $this->info("Successfully chopped {$gameMap->name} into tiles.");
        }

    }
}
