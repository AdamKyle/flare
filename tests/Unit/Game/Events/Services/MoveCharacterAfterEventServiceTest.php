<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Values\MapNameValue;
use App\Game\Events\Services\MoveCharacterAfterEventService;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Values\MapTileValue;
use Facades\App\Flare\Cache\CoordinatesCache as CoordinatesCacheFacade;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event as EventFacade;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class MoveCharacterAfterEventServiceTest extends TestCase
{
    use RefreshDatabase, CreateGameMap;

    private ?MoveCharacterAfterEventService $service = null;

    public function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            UpdateMap::class,
            MoveTimeOutEvent::class,
        ]);

        // Avoid image IO and walking rules during tests:
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnDeathWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnMagma')->andReturn(true);
                $mock->shouldReceive('isPurgatoryWater')->andReturn(false);
                $mock->shouldReceive('isTwistedMemoriesWater')->andReturn(false);
                $mock->shouldReceive('isDelusionalMemoriesWater')->andReturn(false);
                $mock->shouldReceive('getTileColor')->andReturn('000');
            })
        );

        // Provide coordinates so TraverseService can pick safe spots:
        CoordinatesCacheFacade::shouldReceive('getFromCache')
            ->andReturn([
                'x' => [10, 11, 12, 13, 14, 15, 16],
                'y' => [10, 11, 12, 13, 14, 15, 16],
            ])
            ->byDefault();

        // Seed monsters cache for maps used in the test:
        Cache::put('monsters', [
            MapNameValue::SURFACE   => [],
            MapNameValue::LABYRINTH => [],
        ]);

        $this->service = $this->app->make(MoveCharacterAfterEventService::class);
    }

    public function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function testForCharactersOnMapInvokesCallbackWithCharacters(): void
    {
        $surfaceMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $firstCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(10, 10, $surfaceMap)->getCharacter();
        $secondCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(11, 11, $surfaceMap)->getCharacter();

        $collectedCharacterIds = [];
        $callbackInvocations = 0;

        $this->service->forCharactersOnMap($surfaceMap->id, function (EloquentCollection $characters) use (&$collectedCharacterIds, &$callbackInvocations) {
            $callbackInvocations++;
            foreach ($characters as $character) {
                $collectedCharacterIds[] = $character->id;
            }
        });

        sort($collectedCharacterIds);
        $this->assertSame([$firstCharacter->id, $secondCharacter->id], $collectedCharacterIds);
        $this->assertSame(1, $callbackInvocations);
    }

    public function testStopExplorationForDoesNothingWithEmptyCollection(): void
    {
        $emptyCharacters = new EloquentCollection();
        $this->service->stopExplorationFor($emptyCharacters);
        $this->assertTrue(true);
    }

    public function testStopExplorationForStopsExploration(): void
    {
        $surfaceMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $firstCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(5, 5, $surfaceMap)
            ->assignAutomation([])->getCharacter();
        $secondCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(6, 6, $surfaceMap)
            ->assignAutomation([])->getCharacter();

        $this->assertGreaterThan(0, $firstCharacter->currentAutomations()->count());
        $this->assertGreaterThan(0, $secondCharacter->currentAutomations()->count());

        $this->service->stopExplorationFor(new EloquentCollection([$firstCharacter, $secondCharacter]));

        $this->assertSame(0, $firstCharacter->refresh()->currentAutomations()->count());
        $this->assertSame(0, $secondCharacter->refresh()->currentAutomations()->count());
    }

    public function testResetFactionProgressForMapDoesNothingWithEmptyCollection(): void
    {
        $surfaceMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $emptyCharacters = new EloquentCollection();
        $this->service->resetFactionProgressForMap($emptyCharacters, $surfaceMap->id);
        $this->assertTrue(true);
    }

    public function testResetFactionProgressForMapResetsValues(): void
    {
        $surfaceMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $surfaceMap)
            ->assignFactionSystem()
            ->getCharacter();

        $factionBeforeReset = $character->factions()->where('game_map_id', $surfaceMap->id)->first();
        $this->assertNotNull($factionBeforeReset);
        $this->assertNotSame(0, $factionBeforeReset->current_level);

        $this->service->resetFactionProgressForMap(new EloquentCollection([$character]), $surfaceMap->id);

        $factionAfterReset = $character->refresh()->factions()->where('game_map_id', $surfaceMap->id)->first();

        $this->assertSame(0, (int) $factionAfterReset->current_level);
        $this->assertSame(0, (int) $factionAfterReset->current_points);
        $this->assertFalse((bool) $factionAfterReset->maxed);
        $this->assertNull($factionAfterReset->title);
        $this->assertSame(\App\Game\Core\Values\FactionLevel::getPointsNeeded(0), (int) $factionAfterReset->points_needed);
    }

    public function testMoveAllToSurfaceDoesNothingWithEmptyCollection(): void
    {
        $surfaceMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $emptyCharacters = new EloquentCollection();
        $this->service->moveAllToSurface($emptyCharacters, $surfaceMap);
        $this->assertTrue(true);
    }

    public function testMoveAllToSurfaceMovesCharacters(): void
    {
        $surfaceMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);
        $labyrinthMap = $this->createGameMap(['name' => MapNameValue::LABYRINTH, 'default' => false]);

        $firstCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(12, 12, $labyrinthMap)->getCharacter();
        $secondCharacter = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(13, 13, $labyrinthMap)->getCharacter();

        $this->assertSame($labyrinthMap->id, $firstCharacter->map->game_map_id);
        $this->assertSame($labyrinthMap->id, $secondCharacter->map->game_map_id);

        $this->service->moveAllToSurface(new EloquentCollection([$firstCharacter, $secondCharacter]), $surfaceMap);

        $this->assertSame($surfaceMap->id, $firstCharacter->refresh()->map->game_map_id);
        $this->assertSame($surfaceMap->id, $secondCharacter->refresh()->map->game_map_id);
    }
}
