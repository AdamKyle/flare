<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateScheduledEvent;

class InitiateWeeklyCurrencyDropEventTest extends TestCase
{
    use CreateScheduledEvent, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_weekly_currency_event_does_not_trigger()
    {
        Event::fake();

        InitiateWeeklyCurrencyDropEvent::dispatch(3);

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
    }

    public function test_weekly_currency_event_does_trigger()
    {
        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
        ]);

        InitiateWeeklyCurrencyDropEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
    }
}
