<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use App\Flare\Values\MapNameValue;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\UpdateMap;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Services\PortService;
use App\Game\Maps\Services\SetSailService;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class SetSailTraversalTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testSailingIntoTwistedGateTraversesToTwistedMemories(): void
    {
        $this->instance(MapTileValue::class, Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
            $mock->shouldReceive('setUp')->andReturnSelf();
            $mock->shouldReceive('canWalk')->andReturnTrue();
        }));
        Cache::put('coordinates', ['x' => [32], 'y' => [48]]);
        Cache::put('celestial-spawn-rate', 0);
        Queue::fake();
        Event::fake();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 1000000])
            ->getCharacter();
        $origin = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'is_port' => true,
            'x' => 16,
            'y' => 16,
        ]);
        $destinationPort = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'is_port' => true,
            'type' => LocationType::TWISTED_GATE->value,
            'can_players_enter' => true,
            'x' => 32,
            'y' => 16,
        ]);
        $destinationMap = GameMap::factory()->create(['name' => MapNameValue::TWISTED_MEMORIES]);
        Cache::put('monsters', [MapNameValue::SURFACE => [], MapNameValue::TWISTED_MEMORIES => []]);
        $portDetails = resolve(PortService::class)->getPortDetails($character, $origin);
        $authoritativeDestination = $portDetails['port_list']->find($destinationPort->id);
        $setSailService = resolve(SetSailService::class);
        $setSailService->setCoordinatesToTravelTo(32, 16)
            ->setCost($authoritativeDestination->cost)
            ->setTimeOutValue($authoritativeDestination->time);

        $response = $setSailService->setSail($character);

        $this->assertSame(200, $response['status']);
        $this->assertSame($destinationMap->id, $character->refresh()->map->game_map_id);
        Event::assertDispatched(UpdateMap::class);
        Event::assertDispatched(UpdateMonsterList::class);
        Event::assertDispatched(MoveTimeOutEvent::class, fn (MoveTimeOutEvent $event): bool => $event->timeOut === 10 && ! $event->setSail && $event->traverse);
    }
}
