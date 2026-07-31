<?php

namespace App\Flare\GemWorldGeneration\Services;

use App\Flare\Models\GameMap;
use App\Flare\MapGenerator\Schemes\MapColorScheme;
use ChristianEssl\LandmapGeneration\Color\Shader\DetailShader;
use ChristianEssl\LandmapGeneration\Generator\LandmapGenerator;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use ChristianEssl\LandmapGeneration\Utility\ImageUtility;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GemWorldImageGenerator
{
    public function __construct(
        private readonly GemWorldPlaneGenerationSettings $settings,
    ) {}

    public function generate(GameMap $parentMap, string $mapName): string
    {
        ini_set('memory_limit', (string) config('gem_world_generation.memory_limit', '3G'));

        $path = 'generated-gem-worlds/'.Str::slug($mapName).'.png';

        try {
            $mapSettings = (new MapSettings())
                ->setColorScheme(new MapColorScheme(
                    new DetailShader,
                    $this->settings->landColor($parentMap),
                    $this->settings->waterColor($parentMap),
                ))
                ->setWidth((int) config('gem_world_generation.map_width', 2500))
                ->setHeight((int) config('gem_world_generation.map_height', 2500))
                ->setWaterLevel($this->settings->waterLevel($parentMap));

            $landMapGenerator = new LandmapGenerator($mapSettings, Str::slug($mapName));
            $map = $landMapGenerator->generateMap();
            $image = ImageUtility::createImage($map);

            ob_start();
            imagepng($image);
            $imageData = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);

            Storage::disk('maps')->put($path, $imageData);
        } finally {
            unset($map, $image, $landMapGenerator, $mapSettings, $imageData);
            gc_collect_cycles();
        }

        return $path;
    }
}
