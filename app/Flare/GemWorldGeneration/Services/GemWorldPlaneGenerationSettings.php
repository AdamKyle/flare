<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\Models\GameMap;
use ChristianEssl\LandmapGeneration\Color\Shader\DetailShader;
use ChristianEssl\LandmapGeneration\Enum\FillType;
use ChristianEssl\LandmapGeneration\Struct\Color;
use ChristianEssl\LandmapGeneration\Struct\Map;

class GemWorldPlaneGenerationSettings
{
    /**
     * Resolve the configured land color for a Gem World's parent Map.
     *
     * @param GameMap $parentMap
     * @return Color
     */
    public function landColor(GameMap $parentMap): Color
    {
        return match ($parentMap->name) {
            'Labyrinth' => new Color(99, 70, 8),
            'Dungeons' => new Color(94, 74, 73),
            'Shadow Plane' => new Color(128, 127, 126),
            'Hell' => new Color(59, 46, 23),
            'Purgatory' => new Color(0, 0, 0),
            'The Ice Plane' => new Color(39, 84, 166),
            'Twisted Memories' => new Color(91, 110, 96),
            'Delusional Memories' => new Color(138, 79, 12),
            default => new Color(97, 83, 61),
        };
    }

    /**
     * Resolve the configured, unshaded water color for a Gem World's parent Map.
     *
     * @param GameMap $parentMap
     * @return Color
     */
    public function waterColor(GameMap $parentMap): Color
    {
        return match ($parentMap->name) {
            'Dungeons' => new Color(162, 219, 118),
            'Shadow Plane' => new Color(100, 227, 250),
            'Hell' => new Color(97, 0, 16),
            'Purgatory' => new Color(255, 255, 255),
            'The Ice Plane' => new Color(195, 225, 250),
            'Twisted Memories' => new Color(18, 57, 87),
            'Delusional Memories' => new Color(66, 129, 178),
            default => new Color(44, 86, 100),
        };
    }

    /**
     * Resolve the configured water level for a Gem World's parent Map.
     *
     * @param GameMap $parentMap
     * @return int
     */
    public function waterLevel(GameMap $parentMap): int
    {
        return match ($parentMap->name) {
            'Hell' => 55,
            'Purgatory' => 85,
            'The Ice Plane' => 25,
            'Twisted Memories' => 75,
            'Delusional Memories' => 40,
            default => 45,
        };
    }

    /**
     * Resolve the water color as it actually appears on a generated Gem World image, once shaded
     * by the same DetailShader the generator renders with. Generated images are never a flat fill:
     * DetailShader brightens every water pixel by a fixed factor, so Location placement must
     * classify terrain against this shaded color rather than the raw configured water color.
     *
     * @param GameMap $parentMap
     * @return Color
     */
    public function renderedWaterColor(GameMap $parentMap): Color
    {
        return $this->shadedColor($this->waterColor($parentMap), FillType::WATER);
    }

    /**
     * Shade a raw terrain color through the exact shader the Gem World image generator uses.
     *
     * @param Color $color
     * @param int $fillType
     * @return Color
     */
    private function shadedColor(Color $color, int $fillType): Color
    {
        $map = new Map(1, 1);
        $map->fillTypes = [[$fillType]];
        $map->altitudes = [[0]];

        $shader = new DetailShader;
        $shader->createShades($map);

        return $shader->shadeColor($color, 0, 0);
    }
}
