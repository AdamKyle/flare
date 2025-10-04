<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ActiveEvent;
use App\Game\Events\Services\WeeklyCelestialEventEnderService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateScheduledEvent;

class WeeklyCelestialEventEnderServiceTest extends TestCase
{
    use CreateAnnouncement, CreateEvent, CreateScheduledEvent, RefreshDatabase;

    private ?WeeklyCelestialEventEnderService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            GlobalMessageEvent::class,
        ]);

        $this->service = $this->app->make(WeeklyCelestialEventEnderService::class);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_supports_returns_true_only_for_weekly_celestials(): void
    {
        $this->assertTrue($this->service->supports(new EventType(EventType::WEEKLY_CELESTIALS)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WEEKLY_CURRENCY_DROPS)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WEEKLY_FACTION_LOYALTY_EVENT)));
    }

    public function test_end_announces_cleans_announcements_and_deletes_active_event(): void
    {
        $activeEvent = $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'start_date' => now()->subHours(2),
            'currently_running' => true,
        ]);

        $this->createAnnouncement([
            'event_id' => $activeEvent->id,
        ]);

        $this->service->end(new EventType(EventType::WEEKLY_CELESTIALS), $scheduledEvent->fresh(), $activeEvent->fresh());

        EventFacade::assertDispatched(GlobalMessageEvent::class);
        $this->assertEquals(0, Announcement::count());
        $this->assertEquals(0, ActiveEvent::count());
    }
}
