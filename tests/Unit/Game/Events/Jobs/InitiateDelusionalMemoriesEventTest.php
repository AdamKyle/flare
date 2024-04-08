<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
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

class InitiateDelusionalMemoriesEventTest extends TestCase {

    use RefreshDatabase, CreateScheduledEvent, CreateGameMap;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testDeslusionalMemoriesDoesNotTriggerForInvalidScheduledEvent() {
        Event::fake();

        InitiateDelusionalMemoriesEvent::dispatch(rand(10000,99999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
        $this->assertEmpty(GlobalEventGoal::all());
    }

    public function testDelusionalMemoriesEventDoesTriggerScheduledEventExists() {

        $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT
        ]);

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        InitiateDelusionalMemoriesEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());
    }
}
