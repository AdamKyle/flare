<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
use App\Game\Events\Jobs\InitiateFeedbackEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateScheduledEvent;

class InitiateFeedbackEventTest extends TestCase
{
    use CreateGameMap, CreateScheduledEvent, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testFeedBackEventDoesNotTrigger()
    {
        Event::fake();

        InitiateFeedbackEvent::dispatch(rand(10000, 99999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
    }

    public function testFeedbackEventDoseTrigger()
    {

        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
        ]);

        InitiateFeedbackEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
    }
}
