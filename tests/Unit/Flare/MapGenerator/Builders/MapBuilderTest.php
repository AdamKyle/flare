<?php

namespace Tests\Unit\Flare\MapGenerator\Builders;

use App\Flare\MapGenerator\Builders\ImageBuilder;
use App\Flare\MapGenerator\Builders\MapBuilder;
use App\Flare\MapGenerator\Contracts\LandMapImageFactory;
use ChristianEssl\LandmapGeneration\Settings\MapSettings;
use ChristianEssl\LandmapGeneration\Struct\Color;
use Mockery;
use Tests\TestCase;

class MapBuilderTest extends TestCase
{
    public function test_build_map_configures_settings_builds_and_stores_the_image(): void
    {
        $capturedSettings = null;

        $landMapImageFactory = Mockery::mock(LandMapImageFactory::class);
        $landMapImageFactory->shouldReceive('build')
            ->once()
            ->with(Mockery::on(function (MapSettings $settings) use (&$capturedSettings) {
                $capturedSettings = $settings;

                return true;
            }), 'my-seed')
            ->andReturn('fake-image');

        $imageBuilder = Mockery::mock(ImageBuilder::class);
        $imageBuilder->shouldReceive('buildAndStoreImage')->once()->with('fake-image', 'my-map');

        $mapBuilder = new MapBuilder(new MapSettings(), $imageBuilder, $landMapImageFactory);
        $mapBuilder
            ->setLandColor(new Color(1, 1, 1))
            ->setWaterColor(new Color(2, 2, 2))
            ->setMapWidth(250)
            ->setMapHeight(300)
            ->setMapSeed('my-seed')
            ->BuildMap('my-map', 40);

        $this->assertSame(250, $capturedSettings->getWidth());
        $this->assertSame(300, $capturedSettings->getHeight());
        $this->assertSame(40.0, $capturedSettings->getWaterLevel());
    }

    public function test_setters_are_fluent(): void
    {
        $mapBuilder = new MapBuilder(new MapSettings(), Mockery::mock(ImageBuilder::class), Mockery::mock(LandMapImageFactory::class));

        $this->assertSame($mapBuilder, $mapBuilder->setLandColor());
        $this->assertSame($mapBuilder, $mapBuilder->setWaterColor());
        $this->assertSame($mapBuilder, $mapBuilder->setMapHeight());
        $this->assertSame($mapBuilder, $mapBuilder->setMapWidth());
        $this->assertSame($mapBuilder, $mapBuilder->setMapSeed());
    }
}
