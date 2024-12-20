<?php

namespace Tests\Unit\Game\Raids\Services;

use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Services\RaidEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class RaidEventServiceTest extends TestCase
{
    use CreateEvent, CreateItem, CreateLocation, CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    private ?RaidEventService $raidEventService = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->raidEventService = resolve(RaidEventService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->raidEventService = null;
    }

    public function testCreateRaid()
    {
        Event::fake();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'start_date' => now()->addMinutes(5),
            'raid_id' => $raid->id,
            'currently_running' => true,
        ]);

        (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(
            $location->x,
            $location->y,
            $location->map
        );

        Cache::put('monsters', [
            $location->map->name => [
                $monster->toArray(),
            ],
        ]);

        $this->raidEventService->createRaid($raid);

        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testDoNotCreateRaid()
    {
        Event::fake();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
        ]);

        $this->createEvent([
            'raid_id' => $raid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now(),
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'start_date' => now()->addMinutes(5),
            'raid_id' => $raid,
            'currently_running' => true,
        ]);

        $this->raidEventService->createRaid($raid);

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }
}
