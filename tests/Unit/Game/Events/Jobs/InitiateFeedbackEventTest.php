<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
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

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_feed_back_event_does_not_trigger()
    {
        Event::fake();

        InitiateFeedbackEvent::dispatch(rand(10000, 99999));

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
    }

    public function test_feedback_event_dose_trigger()
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
