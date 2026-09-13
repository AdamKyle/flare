<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationConfig;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\MapGenerator\Contracts\MapPixelReader;
use App\Flare\MapGenerator\Contracts\MapPixelReaderFactory;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\LocationTemplateType;
use Illuminate\Support\Facades\Storage;

class GemWorldLocationPlacementService
{
    private const MOVEMENT_BLOCK_SIZE = 16;

    private const MAX_RANDOM_ATTEMPTS = 500;

    /**
     * @param CoordinatesQuery $coordinatesQuery
     * @param GemWorldPlaneGenerationSettings $generationSettings
     * @param MapPixelReaderFactory $mapPixelReaderFactory
     * @param GemWorldGenerationConfig $config
     */
    public function __construct(
        private readonly CoordinatesQuery $coordinatesQuery,
        private readonly GemWorldPlaneGenerationSettings $generationSettings,
        private readonly MapPixelReaderFactory $mapPixelReaderFactory,
        private readonly GemWorldGenerationConfig $config,
    ) {}

    /**
     * Resolve the placed generated Locations for the given generated Gem World Map.
     *
     * @param GameMap $gameMap
     * @return array
     */
    public function placements(GameMap $gameMap): array
    {
        $parentMap = GameMap::find($gameMap->generated_parent_game_map_id);
        $waterColor = $this->waterColor($parentMap);

        $coordinates = $this->coordinatesQuery->get();
        $imageResource = $this->loadMapImage($gameMap);
        $types = [
            ...array_fill(0, 16, LocationTemplateType::REGULAR->value),
            ...array_fill(0, 6, LocationTemplateType::PORT->value),
            ...array_fill(0, 2, LocationTemplateType::DELVE->value),
            ...array_fill(0, 8, LocationTemplateType::SPECIAL->value),
        ];

        $placements = [];
        $usedCoordinates = [];
        $xValues = $coordinates->x;
        $yValues = $coordinates->y;

        $shuffledX = $xValues;
        $shuffledY = $yValues;
        shuffle($shuffledX);
        shuffle($shuffledY);

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

        return $placements;
    }

    /**
     * Resolve the rendered water color to classify terrain against, matching the same shaded
     * color the shared generated terrain palette produces on the parent Map's generated image.
     *
     * @param GameMap|null $parentMap
     * @return array
     */
    private function waterColor(?GameMap $parentMap): array
    {
        if (is_null($parentMap)) {
            return ['red' => 0, 'green' => 0, 'blue' => 0];
        }

        $color = $this->generationSettings->renderedWaterColor($parentMap);

        return ['red' => $color->r, 'green' => $color->g, 'blue' => $color->b];
    }

    /**
     * Scan for the next valid placement coordinate for a Location type, trying shuffled random
     * candidates first, then falling back to a deterministic ordered scan.
     *
     * @param string $type
     * @param array $shuffledX
     * @param array $shuffledY
     * @param array $orderedX
     * @param array $orderedY
     * @param array $usedCoordinates
     * @param MapPixelReader $imageResource
     * @param array $waterColor
     * @param GameMap $generatedMap
     * @param GameMap|null $parentMap
     * @return GemWorldLocationPlacement
     */
    private function nextPlacement(
        string $type,
        array $shuffledX,
        array $shuffledY,
        array $orderedX,
        array $orderedY,
        array &$usedCoordinates,
        MapPixelReader $imageResource,
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
            imageWidth: $imageResource->width(),
            imageHeight: $imageResource->height(),
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
                'Water color tolerance' => $this->config->waterColorTolerance,
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

    /**
     * Load the generated Gem World image as a pixel-readable resource.
     *
     * @param GameMap $gameMap
     * @return MapPixelReader
     */
    private function loadMapImage(GameMap $gameMap): MapPixelReader
    {
        return $this->mapPixelReaderFactory->fromBinary(Storage::disk('maps')->get($gameMap->path));
    }

    /**
     * Determine whether a candidate coordinate is valid terrain for the given Location type.
     *
     * @param string $type
     * @param int $x
     * @param int $y
     * @param MapPixelReader $imageResource
     * @param array $waterColor
     * @param int $landCandidateCount
     * @param int $nearWaterCandidateCount
     * @return bool
     */
    private function canPlaceLocationType(string $type, int $x, int $y, MapPixelReader $imageResource, array $waterColor, int &$landCandidateCount, int &$nearWaterCandidateCount): bool
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

    /**
     * Determine whether any tile adjacent to the coordinate is water.
     *
     * @param int $x
     * @param int $y
     * @param MapPixelReader $imageResource
     * @param array $waterColor
     * @return bool
     */
    private function isNearWater(int $x, int $y, MapPixelReader $imageResource, array $waterColor): bool
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

    /**
     * Determine whether the pixel at the coordinate matches the generated water color.
     *
     * @param int $x
     * @param int $y
     * @param MapPixelReader $imageResource
     * @param array $waterColor
     * @return bool
     */
    private function isWaterTile(int $x, int $y, MapPixelReader $imageResource, array $waterColor): bool
    {
        if ($x < 0 || $y < 0 || $x >= $imageResource->width() || $y >= $imageResource->height()) {
            return false;
        }

        $rgbArray = $imageResource->colorAt($x, $y);

        return $this->colorDistance($rgbArray, $waterColor) <= $this->config->waterColorTolerance;
    }

    /**
     * Count how many sampled pixels across the image match the generated water color, for
     * placement-failure diagnostics.
     *
     * @param MapPixelReader $imageResource
     * @param array $waterColor
     * @return int
     */
    private function sampledWaterCount(MapPixelReader $imageResource, array $waterColor): int
    {
        $count = 0;
        $step = self::MOVEMENT_BLOCK_SIZE;

        for ($x = 0; $x < $imageResource->width(); $x += $step) {
            for ($y = 0; $y < $imageResource->height(); $y += $step) {
                if ($this->isWaterTile($x, $y, $imageResource, $waterColor)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Find the sampled pixel color closest to the generated water color, for placement-failure
     * diagnostics.
     *
     * @param MapPixelReader $imageResource
     * @param array $waterColor
     * @return array
     */
    private function closestColorToWater(MapPixelReader $imageResource, array $waterColor): array
    {
        $closest = ['red' => 0, 'green' => 0, 'blue' => 0];
        $closestDistance = PHP_INT_MAX;
        $step = self::MOVEMENT_BLOCK_SIZE;

        for ($x = 0; $x < $imageResource->width(); $x += $step) {
            for ($y = 0; $y < $imageResource->height(); $y += $step) {
                $rgbArray = $imageResource->colorAt($x, $y);
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

    /**
     * Compute the Euclidean distance between a sampled color and the generated water color.
     *
     * @param array $color
     * @param array $waterColor
     * @return float
     */
    private function colorDistance(array $color, array $waterColor): float
    {
        return sqrt(
            (($color['red'] ?? 0) - $waterColor['red']) ** 2
            + (($color['green'] ?? 0) - $waterColor['green']) ** 2
            + (($color['blue'] ?? 0) - $waterColor['blue']) ** 2
        );
    }

    /**
     * Determine whether the coordinate falls within the spacing radius of an already-used
     * coordinate.
     *
     * @param int $x
     * @param int $y
     * @param array $usedCoordinates
     * @return bool
     */
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
