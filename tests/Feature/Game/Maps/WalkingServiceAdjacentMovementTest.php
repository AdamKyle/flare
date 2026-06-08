<?php

namespace Tests\Feature\Game\Maps;

use App\Flare\Values\MapNameValue;
use App\Game\Maps\Services\WalkingService;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class WalkingServiceAdjacentMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::forget('celestial-spawn-rate');
        Cache::forget('monsters');
        Mockery::close();

        parent::tearDown();
    }

    public function test_player_can_move_one_valid_adjacent_tile(): void
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

    public function test_player_cannot_post_far_coordinates_on_same_map(): void
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

    public function test_rejected_far_movement_does_not_change_character_map_position(): void
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

    public function test_blocked_invalid_terrain_remains_blocked(): void
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
}
