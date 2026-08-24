<?php

namespace Tests\Unit\Flare\MapGenerator\Schemes;

use App\Flare\MapGenerator\Schemes\MapColorScheme;
use ChristianEssl\LandmapGeneration\Color\Shader\NullShader;
use ChristianEssl\LandmapGeneration\Color\Shader\ShaderInterface;
use ChristianEssl\LandmapGeneration\Enum\FillType;
use ChristianEssl\LandmapGeneration\Struct\Color;
use ChristianEssl\LandmapGeneration\Struct\Map;
use Mockery;
use Tests\TestCase;

class MapColorSchemeTest extends TestCase
{
    public function test_defaults_are_used_when_no_colors_or_shader_are_given(): void
    {
        $scheme = new MapColorScheme();

        $this->assertInstanceOf(NullShader::class, $scheme->getShader());

        $map = new Map(1, 2);
        $map->fillTypes[0][0] = FillType::LAND;
        $map->fillTypes[0][1] = FillType::WATER;

        $this->assertEquals(new Color(2, 98, 6), $scheme->getColor($map, 0, 0));
        $this->assertEquals(new Color(24, 94, 188), $scheme->getColor($map, 0, 1));
    }

    public function test_given_colors_and_shader_are_used_when_provided(): void
    {
        $land = new Color(1, 2, 3);
        $water = new Color(4, 5, 6);
        $shadedLand = new Color(10, 20, 30);
        $shadedWater = new Color(40, 50, 60);

        $shader = Mockery::mock(ShaderInterface::class);
        $shader->shouldReceive('shadeColor')->once()->with($land, 0, 0)->andReturn($shadedLand);
        $shader->shouldReceive('shadeColor')->once()->with($water, 0, 1)->andReturn($shadedWater);

        $scheme = new MapColorScheme($shader, $land, $water);

        $this->assertSame($shader, $scheme->getShader());

        $map = new Map(1, 2);
        $map->fillTypes[0][0] = FillType::LAND;
        $map->fillTypes[0][1] = FillType::WATER;

        $this->assertSame($shadedLand, $scheme->getColor($map, 0, 0));
        $this->assertSame($shadedWater, $scheme->getColor($map, 0, 1));
    }
}
