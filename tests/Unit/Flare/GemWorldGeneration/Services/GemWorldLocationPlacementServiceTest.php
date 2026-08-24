<?php

namespace Tests\Unit\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use App\Flare\GemWorldGeneration\Services\GemWorldLocationPlacementService;
use App\Flare\GemWorldGeneration\Services\GemWorldPlaneGenerationSettings;
use App\Flare\GemWorldGeneration\Values\GemWorldGenerationConfig;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\MapGenerator\Contracts\MapPixelReader;
use App\Flare\MapGenerator\Contracts\MapPixelReaderFactory;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\Coordinates;
use App\Game\Maps\Values\LocationTemplateType;
use ChristianEssl\LandmapGeneration\Struct\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class GemWorldLocationPlacementServiceTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_placements_returns_a_placement_for_every_configured_type(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Gem World', 'generated_parent_game_map_id' => null]);

        $xValues = [];
        $yValues = [];
        for ($i = 0; $i < 40; $i++) {
            $xValues[] = $i * 64;
            $yValues[] = $i * 64;
        }

        $coordinatesQuery = Mockery::mock(CoordinatesQuery::class);
        $coordinatesQuery->shouldReceive('get')->once()->andReturn(new Coordinates($xValues, $yValues));

        Storage::shouldReceive('disk')->with('maps')->andReturn(Mockery::mock(['get' => 'binary-data']));

        $reader = Mockery::mock(MapPixelReader::class);
        $reader->shouldReceive('width')->andReturn(3000);
        $reader->shouldReceive('height')->andReturn(3000);
        $reader->shouldReceive('colorAt')->andReturnUsing(function (int $x, int $y) {
            if ($x >= 0 && ($x % 64) === 16) {
                return ['red' => 0, 'green' => 0, 'blue' => 0];
            }

            return ['red' => 255, 'green' => 255, 'blue' => 255];
        });

        $mapPixelReaderFactory = Mockery::mock(MapPixelReaderFactory::class);
        $mapPixelReaderFactory->shouldReceive('fromBinary')->once()->with('binary-data')->andReturn($reader);

        $service = new GemWorldLocationPlacementService(
            $coordinatesQuery,
            new GemWorldPlaneGenerationSettings(),
            $mapPixelReaderFactory,
            GemWorldGenerationConfig::fromConfig(),
        );

        $placements = $service->placements($gameMap);

        $this->assertCount(32, $placements);

        foreach ($placements as $placement) {
            $this->assertInstanceOf(GemWorldLocationPlacement::class, $placement);
        }

        $regularCount = count(array_filter($placements, fn ($p) => $p->type === LocationTemplateType::REGULAR->value));
        $portCount = count(array_filter($placements, fn ($p) => $p->type === LocationTemplateType::PORT->value));
        $delveCount = count(array_filter($placements, fn ($p) => $p->type === LocationTemplateType::DELVE->value));
        $specialCount = count(array_filter($placements, fn ($p) => $p->type === LocationTemplateType::SPECIAL->value));

        $this->assertSame(16, $regularCount);
        $this->assertSame(6, $portCount);
        $this->assertSame(2, $delveCount);
        $this->assertSame(8, $specialCount);
    }

    public function test_placements_uses_the_parent_maps_water_color_when_present(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $gameMap = $this->createGameMap(['name' => 'Gem World', 'generated_parent_game_map_id' => $parentMap->id]);

        $coordinatesQuery = Mockery::mock(CoordinatesQuery::class);
        $coordinatesQuery->shouldReceive('get')->once()->andReturn(new Coordinates([0], [0]));

        Storage::shouldReceive('disk')->with('maps')->andReturn(Mockery::mock(['get' => 'binary-data']));

        $reader = Mockery::mock(MapPixelReader::class);
        $reader->shouldReceive('width')->andReturn(100);
        $reader->shouldReceive('height')->andReturn(100);
        $reader->shouldReceive('colorAt')->andReturn(['red' => 255, 'green' => 255, 'blue' => 255]);

        $mapPixelReaderFactory = Mockery::mock(MapPixelReaderFactory::class);
        $mapPixelReaderFactory->shouldReceive('fromBinary')->once()->andReturn($reader);

        $settings = Mockery::mock(GemWorldPlaneGenerationSettings::class);
        $settings->shouldReceive('waterColor')->once()->with(Mockery::on(fn ($map) => $map->id === $parentMap->id))->andReturn(new Color(10, 20, 30));

        $service = new GemWorldLocationPlacementService($coordinatesQuery, $settings, $mapPixelReaderFactory, GemWorldGenerationConfig::fromConfig());

        $this->expectException(CouldNotPlaceGeneratedGemWorldLocation::class);

        $service->placements($gameMap);
    }

    public function test_placements_throws_when_no_valid_land_tile_exists(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Gem World', 'generated_parent_game_map_id' => null]);

        $coordinatesQuery = Mockery::mock(CoordinatesQuery::class);
        $coordinatesQuery->shouldReceive('get')->once()->andReturn(new Coordinates([0, 16, 32], [0, 16, 32]));

        Storage::shouldReceive('disk')->with('maps')->andReturn(Mockery::mock(['get' => 'binary-data']));

        $reader = Mockery::mock(MapPixelReader::class);
        $reader->shouldReceive('width')->andReturn(100);
        $reader->shouldReceive('height')->andReturn(100);
        $reader->shouldReceive('colorAt')->andReturn(['red' => 0, 'green' => 0, 'blue' => 0]);

        $mapPixelReaderFactory = Mockery::mock(MapPixelReaderFactory::class);
        $mapPixelReaderFactory->shouldReceive('fromBinary')->once()->andReturn($reader);

        $service = new GemWorldLocationPlacementService(
            $coordinatesQuery,
            new GemWorldPlaneGenerationSettings(),
            $mapPixelReaderFactory,
            GemWorldGenerationConfig::fromConfig(),
        );

        $this->expectException(CouldNotPlaceGeneratedGemWorldLocation::class);
        $this->expectExceptionMessageMatches('/Could not place generated gem world location\./');

        $service->placements($gameMap);
    }
}
