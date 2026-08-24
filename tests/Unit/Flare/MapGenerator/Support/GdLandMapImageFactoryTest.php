<?php

namespace Tests\Unit\Flare\MapGenerator\Support;

use App\Flare\MapGenerator\Schemes\MapColorScheme;
use App\Flare\MapGenerator\Support\GdLandMapImageFactory;
use ChristianEssl\LandmapGeneration\Color\Shader\DetailShader;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use ChristianEssl\LandmapGeneration\Struct\Color;
use Tests\TestCase;

class GdLandMapImageFactoryTest extends TestCase
{
    public function test_build_returns_a_gd_image_resource(): void
    {
        $settings = (new MapSettings())
            ->setColorScheme(new MapColorScheme(new DetailShader, new Color(1, 1, 1), new Color(2, 2, 2)))
            ->setWidth(10)
            ->setHeight(10)
            ->setWaterLevel(30);

        $image = (new GdLandMapImageFactory())->build($settings, 'test-seed');

        $this->assertTrue($image instanceof \GdImage || is_resource($image));
    }
}
