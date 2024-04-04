<?php

namespace Tests\Console\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\EventType;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class ProcessScheduledEventsTest extends TestCase {
    use RefreshDatabase, CreateScheduledEvent, CreateRaid, CreateMonster, CreateItem, CreateLocation, CreateGameMap;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testRaidEventTriggers() {
        $gameMap = $this->createGameMap();

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
        ]);

        $item = $this->createItem();

        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
        ]);

        $raid = $this->createRaid([
            'raid_boss_id'                   => $monster->id,
            'raid_monster_ids'               => [$monster->id],
            'raid_boss_location_id'          => $location->id,
            'corrupted_location_ids'         => [$location->id],
            'artifact_item_id'               => $item->id,
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'start_date' => now()->addMinutes(5),
            'raid_id'    => $raid->id,
        ]);

        $this->artisan('process:scheduled-events');

        $this->assertGreaterThan(0, Event::count());
        $this->assertGreaterThan(0, Announcement::count());
    }

    public function testWeeklyCurrencyEventTriggers() {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'start_date' => now()->addMinutes(5),
        ]);

        $this->artisan('process:scheduled-events');

        $this->assertGreaterThan(0, Event::count());
        $this->assertGreaterThan(0, Announcement::count());
    }

    public function testWeeklyCelestialEventTriggers() {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'start_date' => now()->addMinutes(5),
        ]);

        $this->artisan('process:scheduled-events');

        $this->assertGreaterThan(0, Event::count());
        $this->assertGreaterThan(0, Announcement::count());
    }

    public function testMonthlyEventTriggers() {
        $this->createScheduledEvent([
            'event_type' => EventType::MONTHLY_PVP,
            'start_date' => now()->addMinutes(5),
        ]);

        $this->artisan('process:scheduled-events');

        $this->assertGreaterThan(0, Event::count());
        $this->assertGreaterThan(0, Announcement::count());
    }

    public function testWinterEvent() {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => now()->addMinutes(5),
        ]);

        $this->artisan('process:scheduled-events');

        $this->assertGreaterThan(0, Event::count());
        $this->assertGreaterThan(0, Announcement::count());
        $this->assertGreaterThan(0, GlobalEventGoal::count());
    }

    public function testDelusionalMemoriesEvent() {
        $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'start_date' => now()->addMinutes(5),
        ]);

        $this->artisan('process:scheduled-events');

        $this->assertGreaterThan(0, Event::count());
        $this->assertGreaterThan(0, Announcement::count());
        $this->assertGreaterThan(0, GlobalEventGoal::count());
    }

}
