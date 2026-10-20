<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use App\Flare\Values\MapNameValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Values\MapTileValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class TeleportControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testTeleportIgnoresClientCostAndTimeout(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/map/teleport/'.$character->id, [
                'x' => 80,
                'y' => 16,
                'cost' => 0,
                'timeout' => 0,
            ]);

        $response->assertOk();
        $character = $character->refresh();
        $this->assertSame(4000, $character->gold);
        $this->assertFalse($character->can_move);
        $this->assertSame('2026-01-01 12:01:00', $character->can_move_again_at->toDateTimeString());
    }

    public function testOrdinaryTeleportPreservesAuthoritativeGoldTimeoutAndMap(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('monsters', [MapNameValue::SURFACE => []]);
        Queue::fake();
        Carbon::setTestNow('2026-01-01 12:00:00');
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();
        $originalMapId = $character->map->game_map_id;

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/teleport/'.$character->id, [
            'x' => 80,
            'y' => 16,
            'cost' => 1,
            'timeout' => 99,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $character = $character->refresh();
        $this->assertSame(4000, $character->gold);
        $this->assertSame('2026-01-01 12:01:00', $character->can_move_again_at->toDateTimeString());
        $this->assertSame($originalMapId, $character->map->game_map_id);
    }

    public function testTeleportOntoTwistedGateTraversesToTwistedMemories(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );
        Cache::put('celestial-spawn-rate', 0);
        Cache::put('coordinates', ['x' => [80], 'y' => [16]]);
        Queue::fake();
        Event::fake();
        Carbon::setTestNow('2026-03-01 12:00:00');
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();
        $destination = GameMap::factory()->create(['name' => MapNameValue::TWISTED_MEMORIES]);
        Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 80,
            'y' => 16,
            'type' => LocationType::TWISTED_GATE->value,
            'can_players_enter' => true,
        ]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::TWISTED_MEMORIES => []]);

        $this->actingAs($character->user);
        $response = $this->call('POST', '/api/map/teleport/'.$character->id, [
            'x' => 80,
            'y' => 16,
            'cost' => 0,
            'timeout' => 0,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $character = $character->refresh();
        $this->assertSame(4000, $character->gold);
        $this->assertSame($destination->id, $character->map->game_map_id);
        $this->assertFalse($character->can_move);
        $this->assertSame('2026-03-01 12:00:10', $character->can_move_again_at->toDateTimeString());
        Event::assertDispatched(UpdateMap::class);
        Event::assertDispatched(UpdateMonsterList::class);
        Event::assertDispatched(MoveTimeOutEvent::class, fn (MoveTimeOutEvent $event): bool => $event->timeOut === 10 && ! $event->setSail && $event->traverse);
        Event::assertDispatchedTimes(UpdateTopBarEvent::class, 2);
        $topBarEvents = Event::dispatched(UpdateTopBarEvent::class);
        $this->assertTrue($topBarEvents->every(fn (array $arguments): bool => ! $arguments[0]->updateCharacterTops));
    }
}
