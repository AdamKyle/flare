<?php

namespace Tests\Feature\Admin\Gems;

use App\Admin\Console\Commands\GenerateGemMaps;
use App\Flare\GemWorldGeneration\Exceptions\CouldNotPlaceGeneratedGemWorldLocation;
use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\GemWorldGeneration\Services\GemWorldImageGenerator;
use App\Flare\GemWorldGeneration\Services\GemWorldLocationPlacementService;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationTemplateType;
use App\Game\Maps\Services\MovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateLocationTemplate;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GenerateGemMapsCommandTest extends TestCase
{
    use CreateGameLocationGemParamter;
    use CreateGameMap;
    use CreateGameMapGemParamter;
    use CreateLocation;
    use CreateLocationTemplate;
    use CreateRole;
    use CreateUser;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_interactive_command_offers_generation_choices(): void
    {
        $this->mockGenerationDependencies();

        $commandTester = $this->runGenerateGemMapsCommand(['All']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Generating 0 gem maps...', $commandTester->getDisplay());
        $this->assertStringContainsString('Generated gem maps created: 0', $commandTester->getDisplay());
        $this->assertStringContainsString('Finished generating gem maps in 00:00:', $commandTester->getDisplay());
    }

    public function test_map_gem_selection_creates_generated_map_and_locations(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hellfire World',
        ]);

        $commandTester = $this->runGenerateGemMapsCommand([
            'Map Gem',
            'Hell - Hellfire World',
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Generating 1 gem map...', $commandTester->getDisplay());
        $this->assertStringContainsString('[1/1] Hell - Hellfire World', $commandTester->getDisplay());
        $this->assertStringContainsString('Stage: generating image', $commandTester->getDisplay());
        $this->assertStringContainsString('Stage: creating game map record', $commandTester->getDisplay());
        $this->assertStringContainsString('Stage: placing generated locations', $commandTester->getDisplay());
        $this->assertStringContainsString('Generated map gem world for: Hellfire World', $commandTester->getDisplay());
        $this->assertStringContainsString('Finished generating gem maps in 00:00:', $commandTester->getDisplay());

        $generatedMap = $paramter->refresh()->generatedMap;

        $this->assertNotNull($generatedMap);
        $this->assertSame($parentMap->id, $generatedMap->generated_parent_game_map_id);
        $this->assertSame(GeneratedGemMapType::MAP_GEM->value, $generatedMap->generated_map_type);
        $this->assertFalse($generatedMap->can_traverse);
        $this->assertSame('generated/test-map.png', $generatedMap->path);
        $this->assertSame(32, Location::where('game_map_id', $generatedMap->id)->count());
        $this->assertSame($parentMap->id, $generatedMap->monsterSourceGameMap()->id);
        $this->assertTrue($generatedMap->mapType()->isHell());
    }

    public function test_location_gem_selection_creates_generated_map_for_parent_location_map(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $parentMap = $this->createGameMap(['name' => 'Surface', 'default' => true, 'can_traverse' => true]);
        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'name' => 'Shadow Caves']);
        $paramter = $this->createGameLocationGemParamter([
            'location_id' => $location->id,
            'name' => 'Shadow Cave World',
        ]);

        $commandTester = $this->runGenerateGemMapsCommand([
            'Location Gem',
            'Shadow Caves (Surface) - Shadow Cave World',
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Generated location gem world for: Shadow Cave World', $commandTester->getDisplay());

        $generatedMap = $paramter->refresh()->generatedMap;

        $this->assertNotNull($generatedMap);
        $this->assertSame($parentMap->id, $generatedMap->generated_parent_game_map_id);
        $this->assertSame(GeneratedGemMapType::LOCATION_GEM->value, $generatedMap->generated_map_type);
        $this->assertFalse($generatedMap->can_traverse);
    }

    public function test_all_creates_map_and_location_gem_maps_and_skips_existing_maps(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $parentMap = $this->createGameMap(['name' => 'Purgatory', 'default' => false, 'can_traverse' => true]);
        $mapParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $location = $this->createLocation(['game_map_id' => $parentMap->id]);
        $locationParamter = $this->createGameLocationGemParamter(['location_id' => $location->id]);

        $existingGeneratedMap = GameMap::create([
            'name' => 'Existing Map Gem World',
            'path' => 'generated/existing.jpeg',
            'default' => false,
            'kingdom_color' => '#ffffff',
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapParamter->id,
        ]);

        $this->createLocation([
            'game_map_id' => $existingGeneratedMap->id,
            'name' => 'Existing Location',
        ]);

        $commandTester = $this->runGenerateGemMapsCommand(['All']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Skipped existing generated map for: '.$mapParamter->name, $commandTester->getDisplay());
        $this->assertStringContainsString('Generated location gem world for: '.$locationParamter->name, $commandTester->getDisplay());

        $this->assertSame(1, GameMap::where('game_map_gem_paramter_id', $mapParamter->id)->count());
        $this->assertNotNull($locationParamter->refresh()->generatedMap);
    }

    public function test_generated_maps_do_not_appear_in_traverse_list(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $parentMap = $this->createGameMap(['name' => 'Surface', 'default' => true, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);

        $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $maps = resolve(MovementService::class)->getMapsToTraverse($character);

        $this->assertFalse(collect($maps)->pluck('id')->contains($paramter->refresh()->generatedMap->id));
    }

    public function test_admin_map_show_renders_generated_map_metadata(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $admin = $this->createAdmin($this->createAdminRole());
        $parentMap = $this->createGameMap(['name' => 'Surface', 'default' => true, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Surface Gem World',
        ]);

        $result = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter);
        $generatedMap = GameMap::find($result->map_id);

        $this->actingAs($admin)
            ->visit(route('map', ['gameMap' => $generatedMap->id]))
            ->see('Generated Map')
            ->see('Map Gem')
            ->see('Surface Gem World')
            ->see('Parent Map')
            ->see('Monster Source')
            ->see('Walking Rules');
    }

    public function test_image_generator_uses_png_extension(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hell Gem Profile',
        ]);

        $capturedPath = null;
        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')
            ->once()
            ->andReturnUsing(function () use (&$capturedPath): string {
                $capturedPath = 'generated-gem-worlds/hell-gem-profile-map-gem-world.png';

                return $capturedPath;
            });
        $this->app->instance(GemWorldImageGenerator::class, $imageGenerator);

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andReturn([]);
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);

        $result = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter);

        $this->assertStringEndsWith('.png', $capturedPath);
        $this->assertStringNotContainsString('.jpeg', $capturedPath);
        $this->assertStringNotContainsString('.jpg', $capturedPath);
    }

    public function test_placement_failure_result_has_failed_status(): void
    {
        $this->createTemplates();
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hell Gem Profile',
        ]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->andReturn('generated/test-map.png');
        $this->app->instance(GemWorldImageGenerator::class, $imageGenerator);

        $placementException = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 99,
            mapPath: 'generated/test-map.png',
            parentMapName: 'Hell',
            profileName: 'Hell Gem Profile Map Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andThrow($placementException);
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);

        $result = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter);

        $this->assertTrue($result->failed());
        $this->assertFalse($result->generated());
        $this->assertFalse($result->skipped());
    }

    public function test_placement_failure_message_contains_location_type(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 100,
            mapId: 1,
            mapPath: 'generated/map.png',
            parentMapName: 'Shadow Plane',
            profileName: 'Shadow Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $this->assertStringContainsString('port', $exception->getMessage());
        $this->assertStringContainsString('Location type:', $exception->getMessage());
    }

    public function test_placement_failure_message_contains_attempt_count(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 1,
            mapPath: 'generated/map.png',
            parentMapName: 'Shadow Plane',
            profileName: 'Shadow Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $this->assertStringContainsString('24180', $exception->getMessage());
        $this->assertStringContainsString('Attempts:', $exception->getMessage());
    }

    public function test_placement_failure_message_contains_map_id_and_path(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::REGULAR->value,
            attempts: 500,
            mapId: 42,
            mapPath: 'generated-gem-worlds/shadow-gem-world.png',
            parentMapName: 'Shadow Plane',
            profileName: 'Shadow Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $this->assertStringContainsString('42', $exception->getMessage());
        $this->assertStringContainsString('generated-gem-worlds/shadow-gem-world.png', $exception->getMessage());
        $this->assertStringContainsString('Map ID:', $exception->getMessage());
        $this->assertStringContainsString('Map path:', $exception->getMessage());
    }

    public function test_placement_failure_message_contains_parent_map_name(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 5,
            mapPath: 'generated/map.png',
            parentMapName: 'Shadow Plane',
            profileName: 'Shadow Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $this->assertStringContainsString('Shadow Plane', $exception->getMessage());
        $this->assertStringContainsString('Parent map:', $exception->getMessage());
    }

    public function test_placement_failure_message_contains_image_dimensions(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 1,
            mapPath: 'generated/map.png',
            parentMapName: 'Hell',
            profileName: 'Hell Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $this->assertStringContainsString('2500x2500', $exception->getMessage());
        $this->assertStringContainsString('Image dimensions:', $exception->getMessage());
        $this->assertStringContainsString('Image status:', $exception->getMessage());
        $this->assertStringContainsString('loaded', $exception->getMessage());
    }

    public function test_placement_failure_message_contains_water_candidate_counts(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 1,
            mapPath: 'generated/map.png',
            parentMapName: 'Shadow Plane',
            profileName: 'Shadow Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
            diagnostics: [
                'Expected water color' => '100,227,250',
                'Sampled water count' => 42,
                'Land candidate count' => 100,
                'Near-water candidate count' => 0,
            ],
        );

        $this->assertStringContainsString('Expected water color: 100,227,250', $exception->getMessage());
        $this->assertStringContainsString('Sampled water count: 42', $exception->getMessage());
        $this->assertStringContainsString('Land candidate count: 100', $exception->getMessage());
        $this->assertStringContainsString('Near-water candidate count: 0', $exception->getMessage());
    }

    public function test_placement_failure_message_contains_rejection_counts(): void
    {
        $exception = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 1,
            mapPath: 'generated/map.png',
            parentMapName: 'Shadow Plane',
            profileName: 'Shadow Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
            diagnostics: [
                'Rejected because already used count' => 1,
                'Rejected because clustered count' => 2,
                'Rejected because invalid terrain count' => 3,
                'Deterministic scan count' => 4,
            ],
        );

        $this->assertStringContainsString('Rejected because already used count: 1', $exception->getMessage());
        $this->assertStringContainsString('Rejected because clustered count: 2', $exception->getMessage());
        $this->assertStringContainsString('Rejected because invalid terrain count: 3', $exception->getMessage());
        $this->assertStringContainsString('Deterministic scan count: 4', $exception->getMessage());
    }

    public function test_recovery_retries_placement_when_map_gem_exists_with_no_locations(): void
    {
        $this->createTemplates();
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hell Gem Profile',
        ]);

        $existingMap = GameMap::create([
            'name' => 'Hell Gem Profile Map Gem World',
            'path' => 'generated/existing.png',
            'default' => false,
            'kingdom_color' => '#ffffff',
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $paramter->id,
        ]);

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andReturn($this->placements());
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);

        $result = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter->refresh());

        $this->assertTrue($result->generated());
        $this->assertSame($existingMap->id, $result->map_id);
        $this->assertStringContainsString('Recovered and placed locations for existing map', $result->message);
        $this->assertGreaterThan(0, Location::where('game_map_id', $existingMap->id)->count());
    }

    public function test_recovery_skips_map_gem_when_locations_already_exist(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hell Gem Profile',
        ]);

        $result = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter);
        $this->assertTrue($result->generated());

        $secondResult = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter->refresh());

        $this->assertTrue($secondResult->skipped());
        $this->assertStringContainsString('Skipped existing generated map for:', $secondResult->message);
        $this->assertSame(1, GameMap::where('game_map_gem_paramter_id', $paramter->id)->count());
    }

    public function test_command_shows_error_output_for_failed_placement(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hell Gem Profile',
        ]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->andReturn('generated/test-map.png');
        $this->app->instance(GemWorldImageGenerator::class, $imageGenerator);

        $placementException = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 1,
            mapPath: 'generated/test-map.png',
            parentMapName: 'Hell',
            profileName: 'Hell Gem Profile Map Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andThrow($placementException);
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);

        $commandTester = $this->runGenerateGemMapsCommand([
            'Map Gem',
            'Hell - Hell Gem Profile',
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Placement failed for:', $commandTester->getDisplay());
        $this->assertStringContainsString('Could not place generated gem world location', $commandTester->getDisplay());
    }

    public function test_command_summary_shows_failed_count(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hell Gem Profile',
        ]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->andReturn('generated/test-map.png');
        $this->app->instance(GemWorldImageGenerator::class, $imageGenerator);

        $placementException = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 1,
            mapPath: 'generated/test-map.png',
            parentMapName: 'Hell',
            profileName: 'Hell Gem Profile Map Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andThrow($placementException);
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);

        $commandTester = $this->runGenerateGemMapsCommand([
            'Map Gem',
            'Hell - Hell Gem Profile',
        ]);

        $this->assertStringContainsString('Generated gem maps failed: 1', $commandTester->getDisplay());
        $this->assertStringContainsString('Generated gem maps created: 0', $commandTester->getDisplay());
    }

    public function test_failed_placement_still_creates_map_record(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Hell', 'default' => false, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Hell Gem Profile',
        ]);

        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->andReturn('generated/test-map.png');
        $this->app->instance(GemWorldImageGenerator::class, $imageGenerator);

        $placementException = CouldNotPlaceGeneratedGemWorldLocation::withContext(
            type: LocationTemplateType::PORT->value,
            attempts: 24180,
            mapId: 0,
            mapPath: 'generated/test-map.png',
            parentMapName: 'Hell',
            profileName: 'Hell Gem Profile Map Gem World',
            imageWidth: 2500,
            imageHeight: 2500,
            imageLoaded: true,
        );

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andThrow($placementException);
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);

        $result = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter);

        $this->assertTrue($result->failed());
        $this->assertNotNull($result->map_id);
        $this->assertNotNull(GameMap::find($result->map_id));
        $this->assertSame(0, Location::where('game_map_id', $result->map_id)->count());
    }

    public function test_location_gem_recovery_retries_placement_when_map_has_no_locations(): void
    {
        $this->createTemplates();
        $parentMap = $this->createGameMap(['name' => 'Surface', 'default' => true, 'can_traverse' => true]);
        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'name' => 'Shadow Caves']);
        $paramter = $this->createGameLocationGemParamter([
            'location_id' => $location->id,
            'name' => 'Shadow Cave World',
        ]);

        $existingMap = GameMap::create([
            'name' => 'Shadow Cave World Location Gem World',
            'path' => 'generated/existing.png',
            'default' => false,
            'kingdom_color' => '#ffffff',
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $paramter->id,
        ]);

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andReturn($this->placements());
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);

        $result = $this->app->make(GemWorldGenerationService::class)->generateLocationGem($paramter->refresh());

        $this->assertTrue($result->generated());
        $this->assertSame($existingMap->id, $result->map_id);
        $this->assertStringContainsString('Recovered and placed locations for existing map', $result->message);
        $this->assertGreaterThan(0, Location::where('game_map_id', $existingMap->id)->count());
    }

    private function runGenerateGemMapsCommand(array $inputs): CommandTester
    {
        $command = $this->app->make(GenerateGemMaps::class);
        $command->setLaravel($this->app);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs($inputs);
        $commandTester->execute([]);

        return $commandTester;
    }

    private function mockGenerationDependencies(): void
    {
        $imageGenerator = Mockery::mock(GemWorldImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->andReturn('generated/test-map.png');
        $this->app->instance(GemWorldImageGenerator::class, $imageGenerator);

        $placementService = Mockery::mock(GemWorldLocationPlacementService::class);
        $placementService->shouldReceive('placements')->andReturn($this->placements());
        $this->app->instance(GemWorldLocationPlacementService::class, $placementService);
    }

    private function placements(): array
    {
        $types = [
            ...array_fill(0, 16, LocationTemplateType::REGULAR->value),
            ...array_fill(0, 6, LocationTemplateType::PORT->value),
            ...array_fill(0, 2, LocationTemplateType::DELVE->value),
            ...array_fill(0, 8, LocationTemplateType::SPECIAL->value),
        ];

        return collect($types)->map(fn (string $type, int $index): GemWorldLocationPlacement => new GemWorldLocationPlacement(
            $type,
            $index * 16,
            16 + ($index * 16),
        ))->all();
    }

    private function createTemplates(): void
    {
        foreach ($this->placements() as $index => $placement) {
            $this->createLocationTemplate([
                'name' => 'Template '.$index.' '.$placement->type,
                'description' => 'Generated template description '.$index,
                'type' => $placement->type,
                'is_port' => $placement->type === LocationTemplateType::PORT->value,
                'can_players_enter' => true,
            ]);
        }
    }
}
