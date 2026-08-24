<?php

namespace Tests\Console\MapGenerator\Console\Commands;

use App\Flare\MapGenerator\Builders\MapBuilder;
use ChristianEssl\LandmapGeneration\Struct\Color;
use Mockery;
use Tests\TestCase;

class CreateMapTest extends TestCase
{
    public function test_create_map_configures_the_map_builder_and_builds_the_map(): void
    {
        $mapBuilder = Mockery::mock(MapBuilder::class);
        $mapBuilder->shouldReceive('setLandColor')->once()->with(Mockery::type(Color::class))->andReturnSelf();
        $mapBuilder->shouldReceive('setWaterColor')->once()->with(Mockery::type(Color::class))->andReturnSelf();
        $mapBuilder->shouldReceive('setMapHeight')->once()->with('200')->andReturnSelf();
        $mapBuilder->shouldReceive('setMapWidth')->once()->with('100')->andReturnSelf();
        $mapBuilder->shouldReceive('setMapSeed')->once()->with('seed-value')->andReturnSelf();
        $mapBuilder->shouldReceive('BuildMap')->once()->with('Test Map', 40);

        $this->app->instance(MapBuilder::class, $mapBuilder);

        $this->artisan('create:map', [
            'name' => 'Test Map',
            'width' => '100',
            'height' => '200',
            'randomness' => 'seed-value',
        ])->assertExitCode(0);
    }
}
