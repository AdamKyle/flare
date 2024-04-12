<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Game\Events\Jobs\InitiateWeeklyFactionLoyaltyEvent;
use App\Game\Events\Values\EventType;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\GlobalMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateScheduledEvent;

class InitiateWeeklyFactionLoyaltyEventTest extends TestCase {

    use RefreshDatabase, CreateScheduledEvent;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testWeeklyCurrencyEventDoesNotTrigger() {
        Event::fake();

        InitiateWeeklyFactionLoyaltyEvent::dispatch(3);

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
    }

    public function testWeeklyCurrencyEventDoesTrigger() {
        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT
        ]);

        InitiateWeeklyCurrencyDropEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
    }
}
