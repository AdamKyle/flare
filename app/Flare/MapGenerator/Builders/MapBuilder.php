<?php

namespace App\Flare\MapGenerator\Builders;

use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use ChristianEssl\LandmapGeneration\Generator\LandMapGenerator;
use ChristianEssl\LandmapGeneration\Color;
use ChristianEssl\LandmapGeneration\Struct\Color as StructColor;
use ChristianEssl\LandmapGeneration\Color\Shader\DetailShader;
use ChristianEssl\LandmapGeneration\Enum;
use ChristianEssl\LandmapGeneration\Utility\ImageUtility;
use App\Flare\MapGenerator\Builders\ImageBuilder;
use App\Flare\MapGenerator\Schemes\MapColorSheme;

class MapBuilder {

    private $mapSettings;

    private $imageBuilder;

    private $land;

    private $water;

    private $width;

    private $height;

    private $seed;

    public function __construct(MapSettings $mapSettings, ImageBuilder $imageBuilder) {
        $this->mapSettings  = $mapSettings;
        $this->imageBuilder = $imageBuilder;
    }

    public function setLandColor(StructColor $land = null): MapBuilder {
        $this->land = $land;

        return $this;
    }

    public function setWaterColor(StructColor $water = null): MapBuilder {
        $this->water = $water;

        return $this;
    }

    public function setMapHeight(int $height = 500): MapBuilder {
        $this->height = $height;

        return $this;
    }

    public function setMapWidth(int $width = 500): MapBuilder {
        $this->width = $width;

        return $this;
    }

    public function setMapSeed(string $seed = '123'): MapBuilder {
        $this->seed = $seed;

        return $this;
    }

    public function BuildMap(string $mapName): void {
        $settings = $this->mapSettings
            ->setColorScheme(new MapColorSheme(new DetailShader(), $this->land, $this->water))
            ->setWidth($this->width)
            ->setHeight($this->height)
            ->setWaterLevel(30);

        $landMapGenerator = new LandMapGenerator($settings, $this->seed);
        $map              = $landMapGenerator->generateMap();


        $image = ImageUtility::createImage($map);

        $this->imageBuilder->buildAndStoreImage($image, 'public', $mapName);
    }
}
