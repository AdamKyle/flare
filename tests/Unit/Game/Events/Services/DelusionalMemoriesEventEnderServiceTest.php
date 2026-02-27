<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as GameEvent;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Services\DelusionalMemoriesEventEnderService;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
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
use Tests\Traits\CreateScheduledEvent;

class DelusionalMemoriesEventEnderServiceTest extends TestCase
{
    use CreateAnnouncement, CreateEvent, CreateGameMap, CreateScheduledEvent, RefreshDatabase;

    private ?DelusionalMemoriesEventEnderService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            GlobalMessageEvent::class,
            DeleteAnnouncementEvent::class,
            UpdateCharacterStatus::class,
        ]);

        $this->service = app()->make(DelusionalMemoriesEventEnderService::class);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_supports_returns_true_for_delusional_memories_event(): void
    {
        $this->assertTrue($this->service->supports(new EventType(EventType::DELUSIONAL_MEMORIES_EVENT)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WINTER_EVENT)));
    }

    public function test_end_returns_early_when_delusional_map_missing(): void
    {
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $mockRewards = Mockery::mock(KingdomEventService::class, function (MockInterface $m) {
            $m->shouldReceive('handleKingdomRewardsForEvent')->once();
        });
        $this->instance(KingdomEventService::class, $mockRewards);

        $this->service = app()->make(DelusionalMemoriesEventEnderService::class);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->createAnnouncement(['event_id' => $current->id]);

        $this->service->end(new EventType(EventType::DELUSIONAL_MEMORIES_EVENT), $scheduled, $current);

        $this->assertEquals(0, GameEvent::count());
        $this->assertEquals(0, Announcement::count());
    }

    public function test_end_returns_early_when_surface_map_missing(): void
    {
        $this->createGameMap(['name' => MapNameValue::DELUSIONAL_MEMORIES]);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->createAnnouncement(['event_id' => $current->id]);

        $this->service->end(new EventType(EventType::DELUSIONAL_MEMORIES_EVENT), $scheduled, $current);

        $this->assertEquals(0, GameEvent::count());
        $this->assertEquals(0, Announcement::count());
    }

    public function test_end_moves_characters_resets_progress_unpledges_and_cleans_up(): void
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $delusional = $this->createGameMap(['name' => MapNameValue::DELUSIONAL_MEMORIES]);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('setUp')->withAnyArgs()->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnDeathWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnMagma')->andReturn(true);
                $mock->shouldReceive('isPurgatoryWater')->andReturn(false);
                $mock->shouldReceive('isTwistedMemoriesWater')->andReturn(false);
                $mock->shouldReceive('isDelusionalMemoriesWater')->andReturn(false);
                $mock->shouldReceive('getTileColor')->andReturn('000');
            })
        );

        $this->service = app()->make(DelusionalMemoriesEventEnderService::class);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $delusional)
            ->kingdomManagement()
            ->assignKingdom(['game_map_id' => $delusional->id])
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        Cache::put('monsters', [
            MapNameValue::SURFACE => [],
            MapNameValue::DELUSIONAL_MEMORIES => [],
        ]);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->createAnnouncement(['event_id' => $current->id]);

        $this->service->end(new EventType(EventType::DELUSIONAL_MEMORIES_EVENT), $scheduled, $current);

        $character = $character->refresh();

        $this->assertEquals($surface->id, $character->map->game_map_id);
        $this->assertEmpty($character->kingdoms);
        $this->assertEquals(0, GameEvent::count());
        $this->assertEquals(0, Announcement::count());

        EventFacade::assertDispatched(GlobalMessageEvent::class);
        EventFacade::assertDispatched(DeleteAnnouncementEvent::class);
        EventFacade::assertDispatched(UpdateCharacterStatus::class);
    }
}
