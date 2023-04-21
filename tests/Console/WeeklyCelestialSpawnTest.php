<?php

namespace Tests\Console;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Jobs\WeeklyCelestialSpawnEvent;
use Tests\TestCase;

class WeeklyCelestialSpawnTest extends TestCase
{
    use RefreshDatabase;

    public function testIncreaseKingdomTreasury()
    {
        Queue::fake();

        $this->assertEquals(0, $this->artisan('weekly:celestial-spawn'));

        Queue::assertPushed(WeeklyCelestialSpawnEvent::class);

        $this->assertTrue(Cache::has('celestial-spawn-rate'));
        $this->assertTrue(Cache::has('celestial-event-date'));

    }
}
