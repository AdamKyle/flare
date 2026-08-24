<?php

namespace Tests\Unit\Flare\GemWorldGeneration\Services;

use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\GemWorldGeneration\Services\GemWorldImageGenerator;
use App\Flare\GemWorldGeneration\Services\GemWorldLocationPlacementService;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\Models\Location;
use App\Game\Maps\Values\LocationTemplateType;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateLocationTemplate;

class GemWorldGenerationServiceTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateLocation, CreateLocationTemplate, RefreshDatabase;

    public function test_generate_map_gem_generates_a_new_map_tiles_it_and_places_locations(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $gemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);

        $this->createLocationTemplate(['name' => 'Fiery Outpost', 'type' => LocationTemplateType::REGULAR->value]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once()->andReturn('generated-gem-worlds/fiery.png');

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once();

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([
            new GemWorldLocationPlacement(LocationTemplateType::REGULAR->value, 10, 20),
        ]);

        $service = new GemWorldGenerationService($imageGenerator, $placementService, $mapTileGenerationService);

        $result = $service->generateMapGem($gemParamter->fresh());

        $this->assertSame('generated', $result->status);
        $this->assertSame(1, $result->locations_created);
        $this->assertSame(1, Location::where('x', 10)->where('y', 20)->count());
    }

    public function test_generate_map_gem_is_skipped_when_locations_already_exist(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $generatedMap = $this->createGameMap(['name' => 'Fiery Map Gem World', 'generated_parent_game_map_id' => $parentMap->id]);
        $gemParamter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Fiery',
        ]);
        $generatedMap->update(['game_map_gem_paramter_id' => $gemParamter->id]);

        $this->createLocation(['game_map_id' => $generatedMap->id]);

        $service = new GemWorldGenerationService(
            Mockery::mock(GemWorldImageGenerator::class),
            Mockery::mock(GemWorldLocationPlacementService::class),
            Mockery::mock(MapTileGenerationService::class),
        );

        $result = $service->generateMapGem($gemParamter->fresh());

        $this->assertSame('skipped', $result->status);
        $this->assertSame(0, $result->locations_created);
    }

    public function test_generate_map_gem_retries_placements_when_the_map_exists_without_locations(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_parent_game_map_id' => $parentMap->id,
            'path' => 'generated-gem-worlds/fiery.png',
        ]);
        $gemParamter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Fiery',
        ]);
        $generatedMap->update(['game_map_gem_paramter_id' => $gemParamter->id]);

        $this->createLocationTemplate(['name' => 'Fiery Outpost', 'type' => LocationTemplateType::REGULAR->value]);

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([
            new GemWorldLocationPlacement(LocationTemplateType::REGULAR->value, 30, 40),
        ]);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');

        $service = new GemWorldGenerationService(
            Mockery::mock(GemWorldImageGenerator::class),
            $placementService,
            $mapTileGenerationService,
        );

        $result = $service->generateMapGem($gemParamter->fresh());

        $this->assertSame('generated', $result->status);
        $this->assertStringContainsString('Recovered and placed locations for existing map', $result->message);
    }

    public function test_generate_map_gem_returns_a_failed_result_when_placement_fails(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $gemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once()->andReturn('generated-gem-worlds/fiery.png');

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once();

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andThrow(
            CouldNotPlaceGeneratedGemWorldLocation::withContext('regular', 5, 1, 'path', 'Surface', 'Fiery', 10, 10, true)
        );

        $service = new GemWorldGenerationService($imageGenerator, $placementService, $mapTileGenerationService);

        $result = $service->generateMapGem($gemParamter->fresh());

        $this->assertSame('failed', $result->status);
        $this->assertSame(0, $result->locations_created);
    }

    public function test_generate_location_gem_generates_a_new_map(): void
    {
        $gemParamter = $this->createGameLocationGemParamter(['name' => 'Fiery']);
        $gemParamter->loadMissing('location.map');

        $this->createLocationTemplate(['name' => 'Fiery Outpost', 'type' => LocationTemplateType::REGULAR->value]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once()->andReturn('generated-gem-worlds/fiery.png');

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once();

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([
            new GemWorldLocationPlacement(LocationTemplateType::REGULAR->value, 5, 5),
        ]);

        $service = new GemWorldGenerationService($imageGenerator, $placementService, $mapTileGenerationService);

        $result = $service->generateLocationGem($gemParamter->fresh());

        $this->assertSame('generated', $result->status);
        $this->assertSame(1, $result->locations_created);
    }

    public function test_generate_location_gem_retries_placements_when_the_map_exists_without_locations(): void
    {
        $gemParamter = $this->createGameLocationGemParamter(['name' => 'Fiery']);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Location Gem World',
            'path' => 'generated-gem-worlds/fiery.png',
        ]);
        $generatedMap->update(['game_location_gem_paramter_id' => $gemParamter->id]);

        $this->createLocationTemplate(['name' => 'Fiery Outpost', 'type' => LocationTemplateType::REGULAR->value]);

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([
            new GemWorldLocationPlacement(LocationTemplateType::REGULAR->value, 30, 40),
        ]);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');

        $service = new GemWorldGenerationService(
            Mockery::mock(GemWorldImageGenerator::class),
            $placementService,
            $mapTileGenerationService,
        );

        $result = $service->generateLocationGem($gemParamter->fresh());

        $this->assertSame('generated', $result->status);
        $this->assertStringContainsString('Recovered and placed locations for existing map', $result->message);
    }

    public function test_retry_placements_returns_a_failed_result_when_placement_fails(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_parent_game_map_id' => $parentMap->id,
            'path' => 'generated-gem-worlds/fiery.png',
        ]);
        $gemParamter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Fiery',
        ]);
        $generatedMap->update(['game_map_gem_paramter_id' => $gemParamter->id]);

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andThrow(
            CouldNotPlaceGeneratedGemWorldLocation::withContext('regular', 5, 1, 'path', 'Surface', 'Fiery', 10, 10, true)
        );

        $service = new GemWorldGenerationService(
            Mockery::mock(GemWorldImageGenerator::class),
            $placementService,
            Mockery::mock(MapTileGenerationService::class),
        );

        $result = $service->generateMapGem($gemParamter->fresh());

        $this->assertSame('failed', $result->status);
        $this->assertSame(0, $result->locations_created);
    }

    public function test_generate_location_gem_is_skipped_when_locations_already_exist(): void
    {
        $gemParamter = $this->createGameLocationGemParamter(['name' => 'Fiery']);
        $generatedMap = $this->createGameMap(['name' => 'Fiery Location Gem World']);
        $gemParamter->update(['game_location_gem_paramter_id' => $gemParamter->id]);
        $generatedMap->update(['game_location_gem_paramter_id' => $gemParamter->id]);

        $this->createLocation(['game_map_id' => $generatedMap->id]);

        $service = new GemWorldGenerationService(
            Mockery::mock(GemWorldImageGenerator::class),
            Mockery::mock(GemWorldLocationPlacementService::class),
            Mockery::mock(MapTileGenerationService::class),
        );

        $result = $service->generateLocationGem($gemParamter->fresh());

        $this->assertSame('skipped', $result->status);
    }

    public function test_generate_map_gems_maps_over_the_collection(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $gemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);

        $this->createLocationTemplate(['name' => 'Fiery Outpost', 'type' => LocationTemplateType::REGULAR->value]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once()->andReturn('generated-gem-worlds/fiery.png');

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once();

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([]);

        $service = new GemWorldGenerationService($imageGenerator, $placementService, $mapTileGenerationService);

        $results = $service->generateMapGems(new Collection([$gemParamter->fresh()]));

        $this->assertCount(1, $results);
        $this->assertSame('generated', $results->first()->status);
    }

    public function test_generate_location_gems_maps_over_the_collection(): void
    {
        $gemParamter = $this->createGameLocationGemParamter(['name' => 'Fiery']);

        $this->createLocationTemplate(['name' => 'Fiery Outpost', 'type' => LocationTemplateType::REGULAR->value]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once()->andReturn('generated-gem-worlds/fiery.png');

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once();

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([]);

        $service = new GemWorldGenerationService($imageGenerator, $placementService, $mapTileGenerationService);

        $results = $service->generateLocationGems(new Collection([$gemParamter->fresh()]));

        $this->assertCount(1, $results);
    }

    public function test_create_location_from_template_maps_special_and_delve_location_types(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $gemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);

        $this->createLocationTemplate(['name' => 'Fiery Vault', 'type' => LocationTemplateType::SPECIAL->value]);
        $this->createLocationTemplate(['name' => 'Fiery Delve', 'type' => LocationTemplateType::DELVE->value]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once()->andReturn('generated-gem-worlds/fiery.png');

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once();

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([
            new GemWorldLocationPlacement(LocationTemplateType::SPECIAL->value, 1, 1),
            new GemWorldLocationPlacement(LocationTemplateType::DELVE->value, 2, 2),
        ]);

        $service = new GemWorldGenerationService($imageGenerator, $placementService, $mapTileGenerationService);

        $service->generateMapGem($gemParamter->fresh());

        $specialLocation = Location::where('x', 1)->where('y', 1)->first();
        $delveLocation = Location::where('x', 2)->where('y', 2)->first();

        $this->assertSame(LocationType::SPECIAL->value, $specialLocation->type);
        $this->assertSame(LocationType::CAVE_OF_MEMORIES->value, $delveLocation->type);
        $this->assertSame(5, $delveLocation->minutes_between_delve_fights);
    }

    public function test_create_locations_skips_placements_with_no_remaining_template(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $gemParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id, 'name' => 'Fiery']);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once()->andReturn('generated-gem-worlds/fiery.png');

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once();

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->once()->andReturn([
            new GemWorldLocationPlacement(LocationTemplateType::REGULAR->value, 1, 1),
        ]);

        $service = new GemWorldGenerationService($imageGenerator, $placementService, $mapTileGenerationService);

        $result = $service->generateMapGem($gemParamter->fresh());

        $this->assertSame(0, $result->locations_created);
    }
}
