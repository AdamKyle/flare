<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Values\EventType;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\GlobalMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateScheduledEvent;

class InitiateWeeklyCurrencyDropEventTest extends TestCase {

    use RefreshDatabase, CreateScheduledEvent;

    public function setUp(): void {
        parent::setUp();

        foreach (Announcement::all() as $announcement) {
            $announcement->delete();
        }

        foreach (ModelsEvent::all() as $event) {
            $event->delete();
        }
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testWeeklyCurrencyEventDoesNotTrigger() {
        Event::fake();

        InitiateWeeklyCurrencyDropEvent::dispatch(3);

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
    }

    public function testWeeklyCurrencyEventDoesTrigger() {
        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS
        ]);

        InitiateWeeklyCurrencyDropEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
    }
}
