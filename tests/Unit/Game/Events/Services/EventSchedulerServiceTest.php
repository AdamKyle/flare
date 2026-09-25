<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Game\Events\Services\EventSchedulerService;
use App\Game\Events\Values\EventType;
use App\Game\Raids\Services\RaidMapConflictService;
use App\Game\Raids\Values\RaidType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class EventSchedulerServiceTest extends TestCase
{
    use CreateAnnouncement, CreateEvent, CreateLocation, CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    private ?EventSchedulerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EventSchedulerService(new RaidMapConflictService);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_updating_a_currently_running_event_rebuilds_the_announcement_from_the_exact_event(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'ends_at' => now()->addHour(),
        ]);

        $oldAnnouncement = $this->createAnnouncement(['event_id' => $event->id]);

        $newEndDate = now()->addDays(3);

        $this->service->updateEvent([
            'selected_event_type' => EventType::WEEKLY_CELESTIALS,
            'selected_raid' => null,
            'selected_start_date' => $scheduledEvent->start_date,
            'selected_end_date' => $newEndDate,
            'event_description' => $scheduledEvent->description,
        ], $scheduledEvent);

        $this->assertNull(Announcement::find($oldAnnouncement->id));

        $rebuiltAnnouncement = Announcement::where('event_id', $event->id)->first();

        $this->assertNotNull($rebuiltAnnouncement);
        $this->assertStringContainsString('Celestials', $rebuiltAnnouncement->message);
        $this->assertSame($newEndDate->toDateTimeString(), $event->refresh()->ends_at->toDateTimeString());
    }

    public function test_updating_a_currently_running_raid_event_retains_the_exact_raid_identity(): void
    {
        $decoyMonster = $this->createMonster();
        $decoyLocation = $this->createLocation(['game_map_id' => $decoyMonster->game_map_id]);
        $decoyRaid = $this->createRaid([
            'raid_boss_id' => $decoyMonster->id,
            'raid_boss_location_id' => $decoyLocation->id,
            'raid_type' => RaidType::FROZEN_KING,
        ]);
        $this->createEvent(['type' => EventType::RAID_EVENT, 'raid_id' => $decoyRaid->id]);

        $monster = $this->createMonster();
        $location = $this->createLocation(['game_map_id' => $monster->game_map_id]);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $location->id,
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'scheduled_event_id' => $scheduledEvent->id,
            'ends_at' => now()->addHour(),
        ]);

        $this->createAnnouncement(['event_id' => $event->id]);

        $this->service->updateEvent([
            'selected_event_type' => EventType::RAID_EVENT,
            'selected_raid' => $raid->id,
            'selected_start_date' => $scheduledEvent->start_date,
            'selected_end_date' => now()->addDays(2),
            'event_description' => $scheduledEvent->description,
        ], $scheduledEvent);

        $rebuiltAnnouncement = Announcement::where('event_id', $event->id)->first();

        $this->assertNotNull($rebuiltAnnouncement);
        $this->assertStringContainsString($raid->name, $rebuiltAnnouncement->message);
        $this->assertStringNotContainsString($decoyRaid->name, $rebuiltAnnouncement->message);
    }
}
