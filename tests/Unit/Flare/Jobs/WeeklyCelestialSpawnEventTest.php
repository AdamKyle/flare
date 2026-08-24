<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\WeeklyCelestialSpawnEvent;
use App\Flare\Models\Event as GameEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateEvent;

class WeeklyCelestialSpawnEventTest extends TestCase
{
    use CreateEvent, RefreshDatabase;

    public function test_dispatched_job_does_nothing_when_no_celestial_event_exists(): void
    {
        Event::fake([GlobalMessageEvent::class]);

        WeeklyCelestialSpawnEvent::dispatch();

        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function test_dispatched_job_closes_the_event_when_it_has_ended(): void
    {
        $gameEvent = $this->createEvent(['type' => EventType::WEEKLY_CELESTIALS, 'ends_at' => now()->subMinute()]);
        Cache::put('celestial-spawn-rate', 5);

        Event::fake([GlobalMessageEvent::class]);

        WeeklyCelestialSpawnEvent::dispatch();

        $this->assertNull(GameEvent::find($gameEvent->id));
        $this->assertNull(Cache::get('celestial-spawn-rate'));
        Event::assertDispatched(GlobalMessageEvent::class);
    }
}
