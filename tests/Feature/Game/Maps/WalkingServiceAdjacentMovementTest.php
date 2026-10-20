<?php

namespace Tests\Feature\Game\Maps;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use App\Flare\Values\MapNameValue;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Services\WalkingService;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class WalkingServiceAdjacentMovementTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Cache::forget('celestial-spawn-rate');
        Cache::forget('monsters');
        Mockery::close();

        parent::tearDown();
    }

    public function testPlayerCanMoveOneValidAdjacentTile(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->getCharacter();

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(32, $character->refresh()->map->character_position_x);
    }

    public function testPlayerCannotPostFarCoordinatesOnSameMap(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->getCharacter();

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(256, 256);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
    }

    public function testRejectedFarMovementDoesNotChangeCharacterMapPosition(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->getCharacter();

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(256, 256);
        $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(16, $character->refresh()->map->character_position_x);
        $this->assertEquals(16, $character->refresh()->map->character_position_y);
    }

    public function testBlockedInvalidTerrainRemainsBlocked(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(false);
            })
        );

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->getCharacter();

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
    }

    public function testAdjacentMovementOntoNonGateLocationDoesNotTraverseMaps(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        $originalMapId = $character->map->game_map_id;
        Location::factory()->create([
            'game_map_id' => $originalMapId,
            'x' => 32,
            'y' => 16,
            'type' => null,
            'can_players_enter' => true,
        ]);
        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertSame(200, $response['status']);
        $this->assertSame($originalMapId, $character->refresh()->map->game_map_id);
    }

    public function testWalkingOntoTwistedGateTraversesToTwistedMemories(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('coordinates', ['x' => [32], 'y' => [16]]);
        Queue::fake();
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::TWISTED_MEMORIES]);
        Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::TWISTED_GATE->value,
            'can_players_enter' => true,
        ]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::TWISTED_MEMORIES => []]);

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/move/'.$character->id, [
            'character_position_x' => 32,
            'character_position_y' => 16,
            'position_x' => 0,
            'position_y' => 0,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($destination->id, $character->refresh()->map->game_map_id);
        Event::assertDispatched(UpdateMap::class);
        Event::assertDispatched(UpdateMonsterList::class);
        Event::assertDispatched(MoveTimeOutEvent::class, fn (MoveTimeOutEvent $event): bool => $event->timeOut === 10 && ! $event->setSail && $event->traverse);
    }

    public function testWalkingOntoTwistedGateWithoutDestinationMapThrowsExpectedException(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('coordinates', ['x' => [32], 'y' => [16]]);
        Queue::fake();
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();
        Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::TWISTED_GATE->value,
            'can_players_enter' => true,
        ]);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not traverse to Twisted Gate.');

        $walkingService->movePlayerToNewLocation($character);
    }
}
