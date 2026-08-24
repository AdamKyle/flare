<?php

namespace App\Flare\MapGenerator\Support;

use App\Flare\MapGenerator\Contracts\LandMapImageFactory;
use ChristianEssl\LandmapGeneration\Generator\LandmapGenerator;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use ChristianEssl\LandmapGeneration\Utility\ImageUtility;

class GdLandMapImageFactory implements LandMapImageFactory
{
    public function build(MapSettings $settings, string $seed): mixed
    {
        $landMapGenerator = new LandmapGenerator($settings, $seed);
        $map = $landMapGenerator->generateMap();

        return ImageUtility::createImage($map);
    }
}
