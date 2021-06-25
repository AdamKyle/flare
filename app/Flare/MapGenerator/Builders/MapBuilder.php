<?php

namespace App\Flare\MapGenerator\Builders;

use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use ChristianEssl\LandmapGeneration\Generator\LandMapGenerator;
use ChristianEssl\LandmapGeneration\Struct\Color as StructColor;
use ChristianEssl\LandmapGeneration\Color\Shader\DetailShader;
use ChristianEssl\LandmapGeneration\Utility\ImageUtility;
use App\Flare\MapGenerator\Builders\ImageBuilder;
use App\Flare\MapGenerator\Schemes\MapColorSheme;

class MapBuilder {

    /**
     * @var MapSettings $mapSettings
     */
    private $mapSettings;

    /**
     * @var ImageBuilder $imageBuilder
     */
    private $imageBuilder;

    /**
     * @var SturctColor $land | null
     */
    private $land;

    /**
     * @var SturctColor $water | null
     */
    private $water;

    /**
     * @var int $height | 500
     */
    private $width;

    /**
     * @var int $width | 500
     */
    private $height;

    /**
     * @var string $seed | '123'
     */
    private $seed;

    /**
     * Constructor
     *
     * @param MapSettings $mapSettings
     * @param ImageBuilder $imageBuilder
     * @return void
     */
    public function __construct(MapSettings $mapSettings, ImageBuilder $imageBuilder) {
        $this->mapSettings  = $mapSettings;
        $this->imageBuilder = $imageBuilder;
    }

    /**
     * Sets the land color
     *
     * @param StructColor $land | null
     * @return MapBuilder
     */
    public function setLandColor(StructColor $land = null): MapBuilder {
        $this->land = $land;

        return $this;
    }

    /**
     * Sets the water color
     *
     * @param StructColor $water | null
     * @return MapBuilder
     */
    public function setWaterColor(StructColor $water = null): MapBuilder {
        $this->water = $water;

        return $this;
    }

    /**
     * Sets the height of the map.
     *
     * @param int $height | 500
     * @return MapBuilder
     */
    public function setMapHeight(int $height = 500): MapBuilder {
        $this->height = $height;

        return $this;
    }

    /**
     * Sets the width of the map.
     *
     * @param int $width | 500
     * @return MapBuilder
     */
    public function setMapWidth(int $width = 500): MapBuilder {
        $this->width = $width;

        return $this;
    }

    /**
     * Sets the map seed
     *
     * @param string $seed | '123'
     * @return MapBuilder
     */
    public function setMapSeed(string $seed = '123'): MapBuilder {
        $this->seed = $seed;

        return $this;
    }

    /**
     * Builds the maps and stores it.
     *
     * Based on the settings this will build the map using the set colors
     * and dimensions, store it as a jpeg image in the public directory using the $mapName
     * as the file name.
     *
     * @param string $mapName
     * @return void
     */
    public function BuildMap(string $mapName): void {
        $settings = $this->mapSettings
            ->setColorScheme(new MapColorSheme(new DetailShader(), $this->land, $this->water))
            ->setWidth($this->width)
            ->setHeight($this->height)
            ->setWaterLevel(30);

        $landMapGenerator = new LandMapGenerator($settings, $this->seed);
        $map              = $landMapGenerator->generateMap();


        $image = ImageUtility::createImage($map);

        $this->imageBuilder->buildAndStoreImage($image, $mapName);
    }
}
