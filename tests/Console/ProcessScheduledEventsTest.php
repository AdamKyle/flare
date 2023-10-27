<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Values\EventType;
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

        $announcements = Announcement::all();

        foreach ($announcements as $announcement) {
            $announcement->delete();
        }

        $events = Event::all();

        foreach ($events as $event) {
            $event->delete();
        }
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testRaidEventTriggers() {
        $monster = $this->createMonster();
        $item = $this->createItem();

        $this->createGameMap();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id'                   => $monster->id,
            'raid_monster_ids'               => [$monster->id],
            'raid_boss_location_id'          => $location->id,
            'corrupted_location_ids'         => [$location->id],
            'artifact_item_id'               => $item->id,
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'start_date' => now()->addMinutes(5),
            'raid_id'    => $raid,
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
}
