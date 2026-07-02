<?php

namespace Tests\Feature\Admin\Gems;

use App\Admin\Console\Commands\GenerateGemMaps;
use App\Flare\GemWorldGeneration\Services\GemWorldGenerationService;
use App\Flare\GemWorldGeneration\Services\GemWorldImageGenerator;
use App\Flare\GemWorldGeneration\Services\GemWorldLocationPlacementService;
use App\Flare\GemWorldGeneration\Values\GemWorldLocationPlacement;
use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\Maps\Services\MovementService;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationTemplateType;
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

    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testInteractiveCommandOffersGenerationChoices(): void
    {
        $this->mockGenerationDependencies();

        $commandTester = $this->runGenerateGemMapsCommand(['All']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Choices: Map Gem, Location Gem, All', $commandTester->getDisplay());
        $this->assertStringContainsString('Generated gem maps created: 0', $commandTester->getDisplay());
        $this->assertStringContainsString('Finished generating gem maps.', $commandTester->getDisplay());
    }

    public function testMapGemSelectionCreatesGeneratedMapAndLocations(): void
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
        $this->assertStringContainsString('Generated map gem world for: Hellfire World', $commandTester->getDisplay());
        $this->assertStringContainsString('Finished generating gem maps.', $commandTester->getDisplay());

        $generatedMap = $paramter->refresh()->generatedMap;

        $this->assertNotNull($generatedMap);
        $this->assertSame($parentMap->id, $generatedMap->generated_parent_game_map_id);
        $this->assertSame(GeneratedGemMapType::MAP_GEM->value, $generatedMap->generated_map_type);
        $this->assertFalse($generatedMap->can_traverse);
        $this->assertSame('generated/test-map.jpeg', $generatedMap->path);
        $this->assertSame(32, Location::where('game_map_id', $generatedMap->id)->count());
        $this->assertSame($parentMap->id, $generatedMap->monsterSourceGameMap()->id);
        $this->assertTrue($generatedMap->mapType()->isHell());
    }

    public function testLocationGemSelectionCreatesGeneratedMapForParentLocationMap(): void
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

    public function testAllCreatesMapAndLocationGemMapsAndSkipsExistingMaps(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $parentMap = $this->createGameMap(['name' => 'Purgatory', 'default' => false, 'can_traverse' => true]);
        $mapParamter = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $location = $this->createLocation(['game_map_id' => $parentMap->id]);
        $locationParamter = $this->createGameLocationGemParamter(['location_id' => $location->id]);

        GameMap::create([
            'name' => 'Existing Map Gem World',
            'path' => 'generated/existing.jpeg',
            'default' => false,
            'kingdom_color' => '#ffffff',
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $mapParamter->id,
        ]);

        $commandTester = $this->runGenerateGemMapsCommand(['All']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Skipped existing generated map for: '.$mapParamter->name, $commandTester->getDisplay());
        $this->assertStringContainsString('Generated location gem world for: '.$locationParamter->name, $commandTester->getDisplay());

        $this->assertSame(1, GameMap::where('game_map_gem_paramter_id', $mapParamter->id)->count());
        $this->assertNotNull($locationParamter->refresh()->generatedMap);
    }

    public function testGeneratedMapsDoNotAppearInTraverseList(): void
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

    public function testAdminMapShowRendersGeneratedMapMetadata(): void
    {
        $this->createTemplates();
        $this->mockGenerationDependencies();
        $admin = $this->createAdmin($this->createAdminRole());
        $parentMap = $this->createGameMap(['name' => 'Surface', 'default' => true, 'can_traverse' => true]);
        $paramter = $this->createGameMapGemParamter([
            'game_map_id' => $parentMap->id,
            'name' => 'Surface Gem World',
        ]);

        $generatedMap = $this->app->make(GemWorldGenerationService::class)->generateMapGem($paramter);

        $this->actingAs($admin)
            ->visit(route('map', ['gameMap' => $generatedMap->id]))
            ->see('Generated Map')
            ->see('Map Gem')
            ->see('Surface Gem World')
            ->see('Parent Map')
            ->see('Monster Source')
            ->see('Walking Rules');
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
        $imageGenerator->shouldReceive('generate')->andReturn('generated/test-map.jpeg');
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
