<?php

namespace Tests\Unit\Flare\Jobs;

use App\Flare\Jobs\WeeklyCelestialSpawnEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WeeklyCelestialSpawnRateTest extends TestCase
{
    use RefreshDatabase;

    public function testGatesClose() {
        Cache::put('celestial-event-date', now()->subYears(5));
        Cache::put('celestial-spawn-rate', 0.85);

        Event::fake();

        WeeklyCelestialSpawnEvent::dispatch();

        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testGatesCloseEarly() {
        Event::fake();

        WeeklyCelestialSpawnEvent::dispatch();

        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testSpawnEvent() {
        Cache::put('celestial-event-date', now()->addSeconds(10));
        Cache::put('celestial-spawn-rate', 0.85);

        Queue::fake();

        WeeklyCelestialSpawnEvent::dispatch();

        Queue::assertPushed(WeeklyCelestialSpawnEvent::class);
    }
}
