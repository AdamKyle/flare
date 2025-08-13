<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ActiveEvent;
use App\Flare\Models\Location;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Services\RaidEventEnderService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Events\CorruptLocations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class RaidEventEnderServiceTest extends TestCase
{
    use RefreshDatabase,
        CreateGameMap,
        CreateLocation,
        CreateMonster,
        CreateRaid,
        CreateScheduledEvent,
        CreateEvent,
        CreateAnnouncement,
        CreateItem;

    private ?RaidEventEnderService $service = null;

    public function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            GlobalMessageEvent::class,
            CorruptLocations::class,
        ]);

        $this->service = $this->app->make(RaidEventEnderService::class);
    }

    public function tearDown(): void
    {
        $this->service = null;
        parent::tearDown();
    }

    public function testSupportsReturnsTrueOnlyForRaidEvent(): void
    {
        $this->assertTrue($this->service->supports(new EventType(EventType::RAID_EVENT)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WINTER_EVENT)));
        $this->assertFalse($this->service->supports(new EventType(EventType::DELUSIONAL_MEMORIES_EVENT)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WEEKLY_CELESTIALS)));
    }

    public function testEndReturnsEarlyWhenScheduledHasNoRaidAndCleansUp(): void
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $activeEvent = $this->createEvent([
            'type' => EventType::RAID_EVENT,
            'started_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
        ]);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'start_date' => now()->subHours(2),
            'currently_running' => true,
            'raid_id' => null,
        ]);

        $this->createAnnouncement([
            'event_id' => $activeEvent->id,
        ]);

        Cache::put('monsters', [
            $surface->name => [],
        ]);

        $this->service->end(new EventType(EventType::RAID_EVENT), $scheduled->fresh(), $activeEvent->fresh());

        $this->assertEquals(0, Announcement::count());
        $this->assertEquals(0, ActiveEvent::count());
        EventFacade::assertNotDispatched(GlobalMessageEvent::class);
        EventFacade::assertNotDispatched(CorruptLocations::class);
    }

    public function testEndWithRaidUncorruptsLocationsPurgesRaidDataUpdatesMonstersAndCleansUp(): void
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE, 'default' => true]);

        $bossLocation = $this->createLocation([
            'name' => 'Raid Boss Lair',
            'x' => 100,
            'y' => 200,
            'game_map_id' => $surface->id,
            'is_corrupted' => true,
            'has_raid_boss' => true,
            'raid_id' => null,
        ]);

        $corruptedA = $this->createLocation([
            'name' => 'Corrupted A',
            'x' => 101,
            'y' => 201,
            'game_map_id' => $surface->id,
            'is_corrupted' => true,
            'has_raid_boss' => false,
            'raid_id' => null,
        ]);

        $corruptedB = $this->createLocation([
            'name' => 'Corrupted B',
            'x' => 102,
            'y' => 202,
            'game_map_id' => $surface->id,
            'is_corrupted' => true,
            'has_raid_boss' => false,
            'raid_id' => null,
        ]);

        $monster = $this->createMonster();
        $artifactItem = $this->createItem();

        $raid = $this->createRaid([
            'name' => 'Test Raid',
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [$corruptedA->id, $corruptedB->id],
            'artifact_item_id' => $artifactItem->id,
        ]);

        $activeEvent = $this->createEvent([
            'type' => EventType::RAID_EVENT,
            'started_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
            'raid_id' => $raid->id,
        ]);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'start_date' => now()->subHours(2),
            'currently_running' => true,
            'raid_id' => $raid,
        ]);

        $this->createAnnouncement([
            'event_id' => $activeEvent->id,
        ]);

        Cache::put('monsters', [
            $surface->name => [],
        ]);

        (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation($bossLocation->x, $bossLocation->y, $surface)
            ->getCharacter();

        (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation($corruptedA->x, $corruptedA->y, $surface)
            ->getCharacter();

        (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation($corruptedB->x, $corruptedB->y, $surface)
            ->getCharacter();

        $this->service->end(new EventType(EventType::RAID_EVENT), $scheduled->fresh(), $activeEvent->fresh());

        $this->assertEquals(0, ActiveEvent::count());
        $this->assertEquals(0, Announcement::count());

        $this->assertFalse((bool) Location::find($bossLocation->id)->is_corrupted);
        $this->assertFalse((bool) Location::find($bossLocation->id)->has_raid_boss);
        $this->assertNull(Location::find($bossLocation->id)->raid_id);

        $this->assertFalse((bool) Location::find($corruptedA->id)->is_corrupted);
        $this->assertFalse((bool) Location::find($corruptedB->id)->is_corrupted);

        EventFacade::assertDispatched(GlobalMessageEvent::class);
        EventFacade::assertDispatched(CorruptLocations::class);
    }
}
