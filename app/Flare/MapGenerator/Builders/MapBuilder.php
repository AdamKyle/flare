<?php

namespace App\Flare\MapGenerator\Builders;

use App\Flare\MapGenerator\Schemes\MapColorScheme;
use ChristianEssl\LandmapGeneration\Color\Shader\DetailShader;
use ChristianEssl\LandmapGeneration\Generator\LandmapGenerator;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use ChristianEssl\LandmapGeneration\Struct\Color as StructColor;
use ChristianEssl\LandmapGeneration\Utility\ImageUtility;

class MapBuilder
{
    /**
     * @var MapSettings
     */
    private $mapSettings;

    /**
     * @var ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var StructColor | null
     */
    private $land;

    /**
     * @var StructColor | null
     */
    private $water;

    /**
     * @var int | 500
     */
    private $width;

    /**
     * @var int | 500
     */
    private $height;

    /**
     * @var string | '123'
     */
    private $seed;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(MapSettings $mapSettings, ImageBuilder $imageBuilder)
    {
        $this->mapSettings = $mapSettings;
        $this->imageBuilder = $imageBuilder;
    }

    /**
     * Sets the land color
     *
     * @param  StructColor  $land  | null
     */
    public function setLandColor(?StructColor $land = null): MapBuilder
    {
        $this->land = $land;

        return $this;
    }

    /**
     * Sets the water color
     *
     * @param  StructColor  $water  | null
     */
    public function setWaterColor(?StructColor $water = null): MapBuilder
    {
        $this->water = $water;

        return $this;
    }

    /**
     * Sets the height of the map.
     *
     * @param  int  $height  | 500
     */
    public function setMapHeight(int $height = 500): MapBuilder
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Sets the width of the map.
     *
     * @param  int  $width  | 500
     */
    public function setMapWidth(int $width = 500): MapBuilder
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Sets the map seed
     *
     * @param  string  $seed  | '123'
     */
    public function setMapSeed(string $seed = '123'): MapBuilder
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * Builds the maps and stores it.
     *
     * Based on the settings this will build the map using the set colors
     * and dimensions, store it as a jpeg image in the public directory using the $mapName
     * as the file name.
     */
    public function BuildMap(string $mapName, int $waterLevel = 30): void
    {
        $settings = $this->mapSettings
            ->setColorScheme(new MapColorScheme(new DetailShader, $this->land, $this->water))
            ->setWidth($this->width)
            ->setHeight($this->height)
            ->setWaterLevel($waterLevel);

        $landMapGenerator = new LandmapGenerator($settings, $this->seed);
        $map = $landMapGenerator->generateMap();

        $image = ImageUtility::createImage($map);

        $this->imageBuilder->buildAndStoreImage($image, $mapName);
    }
}
