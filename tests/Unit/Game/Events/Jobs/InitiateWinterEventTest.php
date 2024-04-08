<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Values\EventType;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use App\Game\Events\Jobs\InitiateWinterEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\GlobalMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateScheduledEvent;

class InitiateWinterEventTest extends TestCase {

    use RefreshDatabase, CreateScheduledEvent, CreateGameMap;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testWinterEventDoesNotTrigger() {
        Event::fake();

        InitiateWinterEvent::dispatch(rand(1000,9999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
        $this->assertEmpty(GlobalEventGoal::all());
    }

    public function testWinterEventDoesTriggerWhenScheduledEventExists() {

        $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'only_during_event_type' => EventType::WINTER_EVENT
        ]);

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type'        => EventType::WINTER_EVENT,
        ]);

        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());
    }
}
