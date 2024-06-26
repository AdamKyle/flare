<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Maps\Values\MapTileValue;
use Exception;
use Illuminate\Console\Command;
use Facades\App\Flare\Cache\CoordinatesCache;

class MoveKingdomsToLand extends Command
{
    protected $signature = 'move:kingdoms-to-land';
    protected $description = 'Moves kingdoms of those who cannot travel on water to land.';

    private MapTileValue $mapTileValue;
    private UpdateKingdom $updateKingdomHandler;

    public function __construct(MapTileValue $mapTileValue, UpdateKingdom $updateKingdomHandler)
    {
        parent::__construct();
        $this->mapTileValue = $mapTileValue;
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

    public function handle()
    {
        Character::whereHas('kingdoms')->orderBy('id')->chunk(100, function ($characters) {
            foreach ($characters as $character) {
                $this->moveKingdoms($character);
            }
        });
    }

    protected function moveKingdoms(Character $character): void
    {

        $firstWaterTile = $this->findFirstWaterTileForMap($character);

        if (empty($firstWaterTile)) {
            return;
        }

        if (!$this->mapTileValue->canWalk($character, $firstWaterTile['x'], $firstWaterTile['y'])) {
            $this->moveWaterKingdoms($character->kingdoms);
        }
    }

    private function moveWaterKingdoms($kingdoms)
    {
        foreach ($kingdoms as $kingdom) {

            try {
                $tileColor = $this->mapTileValue->getTileColor($kingdom->gameMap, $kingdom->x_position, $kingdom->y_position);

                if (in_array($tileColor, MapTileValue::WATER_TILES)) {
                    $nearestLand = $this->findNearestLand($kingdom->gameMap, $kingdom->x_position, $kingdom->y_position);

                    $kingdom->update([
                        'x_position' => $nearestLand['x'],
                        'y_position' => $nearestLand['y'],
                    ]);
                } else {
                    dump("Kingdom {$kingdom->name} is already on land.");
                }
            } catch (Exception $e) {
                dump("Exception while moving kingdom {$kingdom->name}: " . $e->getMessage());
            }
        }
    }

    private function findNearestLand(GameMap $map, int $x, int $y): array
    {
        $coordinates = CoordinatesCache::getFromCache();
        $spiralCoordinates = $this->generateSpiralCoordinates($coordinates, $x, $y);

        foreach ($spiralCoordinates as $coords) {
            $tileColor = $this->mapTileValue->getTileColor($map, $coords['x'], $coords['y']);
            $isWater = in_array($tileColor, MapTileValue::WATER_TILES);
            $positionIsOccupied = $this->isPositionOccupied($map, $coords['x'], $coords['y']);

            if (!$isWater && !$positionIsOccupied) {
                return ['x' => $coords['x'], 'y' => $coords['y']];
            }
        }

        throw new Exception("No land found for kingdom at position (X/Y): {$x}/{$y}");
    }

    private function generateSpiralCoordinates(array $coordinates, int $startX, int $startY): array
    {
        $spiralCoordinates = [];
        $directions = [[1, 0], [0, 1], [-1, 0], [0, -1]]; // right, down, left, up
        $steps = 1;
        $directionIndex = 0;

        $x = $startX;
        $y = $startY;

        while (true) {
            for ($i = 0; $i < $steps; $i++) {
                $x += $directions[$directionIndex][0];
                $y += $directions[$directionIndex][1];

                if (isset($coordinates['x'][$x]) && isset($coordinates['y'][$y])) {
                    $spiralCoordinates[] = ['x' => $coordinates['x'][$x], 'y' => $coordinates['y'][$y]];
                }
            }

            $directionIndex = ($directionIndex + 1) % 4;

            if ($directionIndex % 2 == 0) {
                $steps++;
            }

            if ($steps > max(count($coordinates['x']), count($coordinates['y']))) {
                break;
            }
        }

        return $spiralCoordinates;
    }


    private function isPositionOccupied(GameMap $map, int $x, int $y): bool
    {
        $kingdom = Kingdom::where('game_map_id', $map->id)
            ->where('x_position', $x)
            ->where('y_position', $y)
            ->first();

        $location = Location::where('game_map_id', $map->id)
            ->where('x', $x)
            ->where('y', $y)
            ->first();

        return !is_null($kingdom) || !is_null($location);
    }

    private function findFirstWaterTileForMap(Character $character): array
    {
        $map = $character->map->gameMap;
        $coordinates = CoordinatesCache::getFromCache();
        $numSamples = 100;

        $sampledCoordinates = array_map(null, array_rand($coordinates['x'], $numSamples), array_rand($coordinates['y'], $numSamples));

        foreach ($sampledCoordinates as $coords) {
            $x = $coordinates['x'][$coords[0]];
            $y = $coordinates['y'][$coords[1]];
            $tileColor = $this->mapTileValue->getTileColor($map, $x, $y);

            if (in_array($tileColor, MapTileValue::WATER_TILES)) {
                $waterTile = ['x' => $x, 'y' => $y];
                return $waterTile;
            }
        }

        return [];
    }
}
