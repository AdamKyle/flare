<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationTemplateType;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GemWorldLocationPlacementService
{
    private const MOVEMENT_BLOCK_SIZE = 16;

    private const MAX_RANDOM_ATTEMPTS = 500;

    public function __construct(
        private readonly CoordinatesCache $coordinatesCache,
        private readonly GemWorldPlaneGenerationSettings $generationSettings,
    ) {}

    /**
     * @return array<int, GemWorldLocationPlacement>
     */
    public function placements(GameMap $gameMap): array
    {
        $parentMap = GameMap::find($gameMap->generated_parent_game_map_id);
        $waterColor = $this->waterColor($parentMap);

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

        $shuffledX = $xValues;
        $shuffledY = $yValues;
        shuffle($shuffledX);
        shuffle($shuffledY);

        try {
            foreach ($types as $type) {
                $placements[] = $this->nextPlacement(
                    $type,
                    $shuffledX,
                    $shuffledY,
                    $xValues,
                    $yValues,
                    $usedCoordinates,
                    $imageResource,
                    $waterColor,
                    $gameMap,
                    $parentMap,
                );
            }
        } finally {
            imagedestroy($imageResource);
        }

        return $placements;
    }

    private function waterColor(?GameMap $parentMap): array
    {
        if (is_null($parentMap)) {
            return ['red' => 0, 'green' => 0, 'blue' => 0];
        }

        $color = $this->generationSettings->waterColor($parentMap);

        return ['red' => $color->r, 'green' => $color->g, 'blue' => $color->b];
    }

    private function nextPlacement(
        string $type,
        array $shuffledX,
        array $shuffledY,
        array $orderedX,
        array $orderedY,
        array &$usedCoordinates,
        mixed $imageResource,
        array $waterColor,
        GameMap $generatedMap,
        ?GameMap $parentMap,
    ): GemWorldLocationPlacement {
        $attempts = 0;
        $randomAttempts = 0;
        $deterministicScanCount = 0;
        $rejectedAlreadyUsed = 0;
        $rejectedClustered = 0;
        $rejectedInvalidTerrain = 0;
        $landCandidateCount = 0;
        $nearWaterCandidateCount = 0;

        foreach ($shuffledX as $x) {
            foreach ($shuffledY as $y) {
                $x = (int) $x;
                $y = (int) $y;
                $key = $x.'-'.$y;

                if ($randomAttempts >= self::MAX_RANDOM_ATTEMPTS) {
                    break 2;
                }

                $randomAttempts++;
                $attempts++;

                if (isset($usedCoordinates[$key])) {
                    $rejectedAlreadyUsed++;

                    continue;
                }

                if ($this->isClustered($x, $y, $usedCoordinates)) {
                    $rejectedClustered++;

                    continue;
                }

                if (! $this->canPlaceLocationType($type, $x, $y, $imageResource, $waterColor, $landCandidateCount, $nearWaterCandidateCount)) {
                    $rejectedInvalidTerrain++;

                    continue;
                }

                $usedCoordinates[$key] = true;

                return new GemWorldLocationPlacement($type, $x, $y);
            }
        }

        foreach ($orderedX as $x) {
            foreach ($orderedY as $y) {
                $x = (int) $x;
                $y = (int) $y;
                $key = $x.'-'.$y;
                $attempts++;
                $deterministicScanCount++;

                if (isset($usedCoordinates[$key])) {
                    $rejectedAlreadyUsed++;

                    continue;
                }

                if ($this->isClustered($x, $y, $usedCoordinates)) {
                    $rejectedClustered++;

                    continue;
                }

                if (! $this->canPlaceLocationType($type, $x, $y, $imageResource, $waterColor, $landCandidateCount, $nearWaterCandidateCount)) {
                    $rejectedInvalidTerrain++;

                    continue;
                }

                $usedCoordinates[$key] = true;

                return new GemWorldLocationPlacement($type, $x, $y);
            }
        }

        throw CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: $type,
            attempts: $attempts,
            mapId: $generatedMap->id,
            mapPath: $generatedMap->path,
            parentMapName: $parentMap?->name ?? 'unknown',
            profileName: $generatedMap->name,
            imageWidth: imagesx($imageResource),
            imageHeight: imagesy($imageResource),
            imageLoaded: true,
            diagnostics: [
                'Generated map name' => $generatedMap->name,
                'Parent map ID' => $parentMap?->id ?? 'unknown',
                'Map type' => $generatedMap->generated_map_type ?? 'unknown',
                'Max random attempts' => self::MAX_RANDOM_ATTEMPTS,
                'Deterministic scan count' => $deterministicScanCount,
                'Image path' => $generatedMap->path,
                'File extension' => pathinfo($generatedMap->path, PATHINFO_EXTENSION),
                'Image decoder/load method' => 'imagecreatefromstring',
                'Expected water color' => implode(',', $waterColor),
                'Water color tolerance' => (int) config('gem_world_generation.water_color_tolerance', 70),
                'Sampled water count' => $this->sampledWaterCount($imageResource, $waterColor),
                'Closest water color found' => implode(',', $this->closestColorToWater($imageResource, $waterColor)),
                'Land candidate count' => $landCandidateCount,
                'Near-water candidate count' => $nearWaterCandidateCount,
                'Rejected because already used count' => $rejectedAlreadyUsed,
                'Rejected because clustered count' => $rejectedClustered,
                'Rejected because invalid terrain count' => $rejectedInvalidTerrain,
                'Existing generated locations count' => Location::where('game_map_id', $generatedMap->id)->count(),
                'Recovery attempted' => 'yes',
                'Recovery still possible' => 'no valid candidate found after random and deterministic scan',
            ],
        );
    }

    private function loadMapImage(GameMap $gameMap): mixed
    {
        $imageResource = imagecreatefromstring(Storage::disk('maps')->get($gameMap->path));

        if ($imageResource === false) {
            throw new RuntimeException('Could not load generated gem world map image.');
        }

        return $imageResource;
    }

    private function canPlaceLocationType(string $type, int $x, int $y, mixed $imageResource, array $waterColor, int &$landCandidateCount, int &$nearWaterCandidateCount): bool
    {
        if ($this->isWaterTile($x, $y, $imageResource, $waterColor)) {
            return false;
        }

        $landCandidateCount++;

        if ($type === LocationTemplateType::PORT->value) {
            $isNearWater = $this->isNearWater($x, $y, $imageResource, $waterColor);

            if ($isNearWater) {
                $nearWaterCandidateCount++;
            }

            return $isNearWater;
        }

        return true;
    }

    private function isNearWater(int $x, int $y, mixed $imageResource, array $waterColor): bool
    {
        $adjacentCoordinates = [
            [$x + self::MOVEMENT_BLOCK_SIZE, $y],
            [$x - self::MOVEMENT_BLOCK_SIZE, $y],
            [$x, $y + self::MOVEMENT_BLOCK_SIZE],
            [$x, $y - self::MOVEMENT_BLOCK_SIZE],
        ];

        foreach ($adjacentCoordinates as [$adjacentX, $adjacentY]) {
            if ($this->isWaterTile($adjacentX, $adjacentY, $imageResource, $waterColor)) {
                return true;
            }
        }

        return false;
    }

    private function isWaterTile(int $x, int $y, mixed $imageResource, array $waterColor): bool
    {
        if ($x < 0 || $y < 0 || $x >= imagesx($imageResource) || $y >= imagesy($imageResource)) {
            return false;
        }

        $rgbIndex = imagecolorat($imageResource, $x, $y);
        $rgbArray = imagecolorsforindex($imageResource, $rgbIndex);

        return $this->colorDistance($rgbArray, $waterColor) <= (int) config('gem_world_generation.water_color_tolerance', 70);
    }

    private function sampledWaterCount(mixed $imageResource, array $waterColor): int
    {
        $count = 0;
        $step = self::MOVEMENT_BLOCK_SIZE;

        for ($x = 0; $x < imagesx($imageResource); $x += $step) {
            for ($y = 0; $y < imagesy($imageResource); $y += $step) {
                if ($this->isWaterTile($x, $y, $imageResource, $waterColor)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function closestColorToWater(mixed $imageResource, array $waterColor): array
    {
        $closest = ['red' => 0, 'green' => 0, 'blue' => 0];
        $closestDistance = PHP_INT_MAX;
        $step = self::MOVEMENT_BLOCK_SIZE;

        for ($x = 0; $x < imagesx($imageResource); $x += $step) {
            for ($y = 0; $y < imagesy($imageResource); $y += $step) {
                $rgbArray = imagecolorsforindex($imageResource, imagecolorat($imageResource, $x, $y));
                $distance = $this->colorDistance($rgbArray, $waterColor);

                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $closest = [
                        'red' => $rgbArray['red'],
                        'green' => $rgbArray['green'],
                        'blue' => $rgbArray['blue'],
                    ];
                }
            }
        }

        return $closest;
    }

    private function colorDistance(array $color, array $waterColor): float
    {
        return sqrt(
            (($color['red'] ?? 0) - $waterColor['red']) ** 2
            + (($color['green'] ?? 0) - $waterColor['green']) ** 2
            + (($color['blue'] ?? 0) - $waterColor['blue']) ** 2
        );
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
