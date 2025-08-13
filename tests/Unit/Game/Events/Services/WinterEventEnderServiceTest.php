<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as GameEvent;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\WeaponTypes;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Events\Services\WinterEventEnderService;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event as EventFacade;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateScheduledEvent;

class WinterEventEnderServiceTest extends TestCase
{
    use RefreshDatabase, CreateGameMap, CreateEvent, CreateScheduledEvent, CreateAnnouncement, CreateItem;

    private ?WinterEventEnderService $service = null;

    public function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            GlobalMessageEvent::class,
            UpdateCharacterStatus::class,
        ]);

        $this->service = app()->make(WinterEventEnderService::class);
    }

    public function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function testSupportsReturnsTrueOnlyForWinterEvent(): void
    {
        $this->assertTrue($this->service->supports(new EventType(EventType::WINTER_EVENT)));
        $this->assertFalse($this->service->supports(new EventType(EventType::DELUSIONAL_MEMORIES_EVENT)));
    }

    public function testEndReturnsEarlyWhenIceMapMissing(): void
    {
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $this->instance(
            KingdomEventService::class,
            Mockery::mock(KingdomEventService::class, function (MockInterface $mock) {
                $mock->shouldReceive('handleKingdomRewardsForEvent')->once();
            })
        );

        $this->service = app()->make(WinterEventEnderService::class);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->createAnnouncement(['event_id' => $current->id]);

        $this->service->end(new EventType(EventType::WINTER_EVENT), $scheduled, $current);

        $this->assertEquals(0, GameEvent::count());
        $this->assertEquals(0, Announcement::count());
        EventFacade::assertDispatchedTimes(GlobalMessageEvent::class, 0);
    }

    public function testEndReturnsEarlyWhenSurfaceMapMissing(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);

        $this->instance(
            KingdomEventService::class,
            Mockery::mock(KingdomEventService::class, function (MockInterface $mock) {
                $mock->shouldReceive('handleKingdomRewardsForEvent')->once();
            })
        );

        $this->service = app()->make(WinterEventEnderService::class);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->createAnnouncement(['event_id' => $current->id]);

        $this->service->end(new EventType(EventType::WINTER_EVENT), $scheduled, $current);

        $this->assertEquals(0, GameEvent::count());
        $this->assertEquals(0, Announcement::count());
        EventFacade::assertDispatchedTimes(GlobalMessageEvent::class, 0);
    }

    public function testEndMovesCharactersResetsProgressUnpledgesAndCleansUp(): void
    {
        $surfaceMap = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $iceMap = $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);

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

        $this->service = app()->make(WinterEventEnderService::class);

        Cache::put('monsters', [
            MapNameValue::SURFACE => [],
            MapNameValue::ICE_PLANE => [],
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $iceMap)
            ->kingdomManagement()
            ->assignKingdom(['game_map_id' => $iceMap->id])
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $this->createItem(['specialty_type' => ItemSpecialtyType::CORRUPTED_ICE, 'type' => WeaponTypes::HAMMER]);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->createAnnouncement(['event_id' => $current->id]);

        $this->service->end(new EventType(EventType::WINTER_EVENT), $scheduled, $current);

        $character = $character->refresh();

        $this->assertEquals($surfaceMap->id, $character->map->game_map_id);
        $this->assertEmpty($character->kingdoms);
        $this->assertEquals(0, GameEvent::count());
        $this->assertEquals(0, Announcement::count());

        EventFacade::assertDispatched(GlobalMessageEvent::class);
        EventFacade::assertDispatched(UpdateCharacterStatus::class);
    }
}
