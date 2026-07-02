<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\Models\GameMap;
use App\Flare\Values\LocationTemplateType;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GemWorldLocationPlacementService
{
    private const MOVEMENT_BLOCK_SIZE = 16;

    public function __construct(
        private readonly CoordinatesCache $coordinatesCache,
    ) {}

    /**
     * @return array<int, GemWorldLocationPlacement>
     */
    public function placements(GameMap $gameMap): array
    {
        $coordinates = $this->coordinatesCache->getFromCache();
        $imageResource = $this->loadMapImage($gameMap);
        $types = [
            ...array_fill(0, 16, LocationTemplateType::REGULAR->value),
            ...array_fill(0, 6, LocationTemplateType::PORT->value),
            ...array_fill(0, 2, LocationTemplateType::DELVE->value),
            ...array_fill(0, 8, LocationTemplateType::SPECIAL->value),
        ];

        $placements = [];
        $usedCoordinates = [];
        $xValues = $coordinates['x'];
        $yValues = $coordinates['y'];

        shuffle($xValues);
        shuffle($yValues);

        try {
            foreach ($types as $type) {
                $placements[] = $this->nextPlacement($type, $xValues, $yValues, $usedCoordinates, $imageResource);
            }
        } finally {
            imagedestroy($imageResource);
        }

        return $placements;
    }

    private function nextPlacement(
        string $type,
        array $xValues,
        array $yValues,
        array &$usedCoordinates,
        mixed $imageResource,
    ): GemWorldLocationPlacement {
        foreach ($xValues as $x) {
            foreach ($yValues as $y) {
                $x = (int) $x;
                $y = (int) $y;
                $key = $x.'-'.$y;

                if (isset($usedCoordinates[$key])) {
                    continue;
                }

                if ($this->isClustered($x, $y, $usedCoordinates)) {
                    continue;
                }

                if (! $this->canPlaceLocationType($type, $x, $y, $imageResource)) {
                    continue;
                }

                $usedCoordinates[$key] = true;

                return new GemWorldLocationPlacement($type, $x, $y);
            }
        }

        throw new RuntimeException('Could not place generated gem world location.');
    }

    private function loadMapImage(GameMap $gameMap): mixed
    {
        $imageResource = imagecreatefromstring(Storage::disk('maps')->get($gameMap->path));

        if ($imageResource === false) {
            throw new RuntimeException('Could not load generated gem world map image.');
        }

        return $imageResource;
    }

    private function canPlaceLocationType(string $type, int $x, int $y, mixed $imageResource): bool
    {
        if ($this->isWaterTile($x, $y, $imageResource)) {
            return false;
        }

        if ($type === LocationTemplateType::PORT->value) {
            return $this->isNearWater($x, $y, $imageResource);
        }

        return true;
    }

    private function isNearWater(int $x, int $y, mixed $imageResource): bool
    {
        $adjacentCoordinates = [
            [$x + self::MOVEMENT_BLOCK_SIZE, $y],
            [$x - self::MOVEMENT_BLOCK_SIZE, $y],
            [$x, $y + self::MOVEMENT_BLOCK_SIZE],
            [$x, $y - self::MOVEMENT_BLOCK_SIZE],
        ];

        foreach ($adjacentCoordinates as [$adjacentX, $adjacentY]) {
            if ($this->isWaterTile($adjacentX, $adjacentY, $imageResource)) {
                return true;
            }
        }

        return false;
    }

    private function isWaterTile(int $x, int $y, mixed $imageResource): bool
    {
        if ($x < 0 || $y < 0 || $x >= imagesx($imageResource) || $y >= imagesy($imageResource)) {
            return false;
        }

        $rgbIndex = imagecolorat($imageResource, $x, $y);
        $rgbArray = imagecolorsforindex($imageResource, $rgbIndex);
        $tileColor = (int) ($rgbArray['red'].$rgbArray['green'].$rgbArray['blue']);

        return in_array($tileColor, MapTileValue::WATER_TILES, true);
    }

    private function isClustered(int $x, int $y, array $usedCoordinates): bool
    {
        foreach (array_keys($usedCoordinates) as $coordinate) {
            [$usedX, $usedY] = array_map('intval', explode('-', $coordinate));

            if (abs($usedX - $x) <= 32 && abs($usedY - $y) <= 32) {
                return true;
            }
        }

        return false;
    }
}
