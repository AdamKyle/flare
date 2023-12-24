<?php

namespace Tests\Game\Raids\Services;


use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Flare\Models\Event as GamEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Services\RaidEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class RaidEventServiceTest extends TestCase {

    use RefreshDatabase, CreateMonster, CreateItem, CreateLocation, CreateRaid, CreateScheduledEvent;

    private ?RaidEventService $raidEventService = null;

    public function setUp(): void {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GamEvent::truncate();
        ScheduledEvent::truncate();
        Raid::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->raidEventService = resolve(RaidEventService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->raidEventService = null;
    }

    public function testCreateRaid() {
        Event::fake();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id'                   => $monster->id,
            'raid_monster_ids'               => [$monster->id],
            'raid_boss_location_id'          => $location->id,
            'corrupted_location_ids'         => [$location->id],
            'artifact_item_id'               => $item->id,
        ]);

        $this->createScheduledEvent([
            'event_type'        => EventType::RAID_EVENT,
            'start_date'        => now()->addMinutes(5),
            'raid_id'           => $raid,
            'currently_running' => true,
        ]);

        $this->raidEventService->createRaid($raid);

        Event::assertDispatched(GlobalMessageEvent::class);
    }
}
