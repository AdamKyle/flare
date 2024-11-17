<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateWinterEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class InitiateWinterEventTest extends TestCase
{
    use CreateGameMap, CreateScheduledEvent, RefreshDatabase, CreateRaid, CreateItem, CreateLocation, CreateMonster;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testWinterEventDoesNotTrigger()
    {
        Event::fake();

        InitiateWinterEvent::dispatch(rand(1000, 9999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
        $this->assertEmpty(GlobalEventGoal::all());
    }

    public function testWinterEventDoesTriggerWhenScheduledEventExists()
    {

        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
        ]);

        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());
    }

    public function testScheduleTheEventForNextYear()
    {
        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        Event::fake();

        $now = now();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $now,
            'end_date' => $now,
        ]);

        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());

        $eventForNextYear = ScheduledEvent::where('start_date', $now->clone()->addYear())->where('event_type', EventType::WINTER_EVENT)->first();

        $this->assertNotNull($eventForNextYear);
        $this->assertNull($eventForNextYear->raids_for_event);
    }

    public function testScheduleTheEventAndAssociatedRaidsForNextYear()
    {

        $monster = $this->createMonster();
        $item = $this->createItem();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
            'scheduled_event_description' => 'test description'
        ]);

        $now = now();

        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $now,
            'end_date' => $now,
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $now,
                'end_date' => $now,
            ]]
        ]);


        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());


        $eventForNextYear = ScheduledEvent::where('start_date', $now->clone()->addYear())->where('event_type', EventType::WINTER_EVENT)->first();

        $this->assertNotNull($eventForNextYear);
        $this->assertNotNull($eventForNextYear->raids_for_event);
    }
}
