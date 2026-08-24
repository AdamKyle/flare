<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Values\GemWorldGenerationConfig;
use App\Flare\MapGenerator\Contracts\LandMapImageFactory;
use App\Flare\MapGenerator\Schemes\MapColorScheme;
use App\Flare\MapGenerator\Support\GdPngImageWriter;
use App\Flare\Models\GameMap;
use ChristianEssl\LandmapGeneration\Color\Shader\DetailShader;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use Illuminate\Support\Str;

class GemWorldImageGenerator
{
    public function __construct(
        private readonly GemWorldPlaneGenerationSettings $settings,
        private readonly LandMapImageFactory $landMapImageFactory,
        private readonly GdPngImageWriter $imageWriter,
        private readonly GemWorldGenerationConfig $config,
    ) {}

    public function generate(GameMap $parentMap, string $mapName): string
    {
        ini_set('memory_limit', $this->config->memoryLimit);

        $path = 'generated-gem-worlds/'.Str::slug($mapName).'.png';

        try {
            $mapSettings = (new MapSettings())
                ->setColorScheme(new MapColorScheme(
                    new DetailShader,
                    $this->settings->landColor($parentMap),
                    $this->settings->waterColor($parentMap),
                ))
                ->setWidth($this->config->mapWidth)
                ->setHeight($this->config->mapHeight)
                ->setWaterLevel($this->settings->waterLevel($parentMap));

            $image = $this->landMapImageFactory->build($mapSettings, Str::slug($mapName));

            $this->imageWriter->encodeAndStore($image, 'maps', $path);
        } finally {
            unset($image, $mapSettings);
            gc_collect_cycles();
        }

        return $path;
    }
}
