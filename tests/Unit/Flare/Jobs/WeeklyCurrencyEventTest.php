<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\WeeklyCurrencyEvent;
use App\Flare\Models\Event as GameEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateEvent;

class WeeklyCurrencyEventTest extends TestCase
{
    use CreateEvent, RefreshDatabase;

    public function test_dispatched_job_does_nothing_when_no_currency_event_exists(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        WeeklyCurrencyEvent::dispatch();

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function test_dispatched_job_closes_the_event_when_it_has_ended(): void
    {
        $gameEvent = $this->createEvent(['type' => EventType::WEEKLY_CURRENCY_DROPS, 'ends_at' => now()->subMinute()]);

        Event::fake([GlobalMessageEvent::class]);

        WeeklyCurrencyEvent::dispatch();

        $this->assertNull(GameEvent::find($gameEvent->id));
        Event::assertDispatched(GlobalMessageEvent::class);
    }
}
