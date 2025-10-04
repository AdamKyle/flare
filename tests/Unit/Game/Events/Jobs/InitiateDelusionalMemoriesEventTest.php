<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateScheduledEvent;

class InitiateDelusionalMemoriesEventTest extends TestCase
{
    use CreateGameMap, CreateScheduledEvent, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_deslusional_memories_does_not_trigger_for_invalid_scheduled_event()
    {
        Event::fake();

        InitiateDelusionalMemoriesEvent::dispatch(rand(10000, 99999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
        $this->assertEmpty(GlobalEventGoal::all());
    }

    public function test_delusional_memories_event_does_trigger_scheduled_event_exists()
    {

        $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
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
