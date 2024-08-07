<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateScheduledEvent;

class InitiateWeeklyCelestialSpawnEventTest extends TestCase
{
    use CreateScheduledEvent, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testWeeklyCelestialEventDoesNotTrigger()
    {
        Event::fake();

        InitiateWeeklyCelestialSpawnEvent::dispatch(3);

        Event::assertNotDispatched(GlobalMessageEvent::class);

        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
    }

    public function testWeeklyCelestialEventDoesTrigger()
    {
        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
        ]);

        InitiateWeeklyCelestialSpawnEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
    }
}
