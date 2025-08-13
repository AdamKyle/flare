<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ActiveEvent;
use App\Game\Events\Services\WeeklyCurrencyEventEnderService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateScheduledEvent;

class WeeklyCurrencyEventEnderServiceTest extends TestCase
{
    use RefreshDatabase, CreateEvent, CreateScheduledEvent, CreateAnnouncement;

    private ?WeeklyCurrencyEventEnderService $service = null;

    public function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            GlobalMessageEvent::class,
        ]);

        $this->service = $this->app->make(WeeklyCurrencyEventEnderService::class);
    }

    public function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function testSupportsReturnsTrueOnlyForWeeklyCurrencyDrops(): void
    {
        $this->assertTrue($this->service->supports(new EventType(EventType::WEEKLY_CURRENCY_DROPS)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WEEKLY_CELESTIALS)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WEEKLY_FACTION_LOYALTY_EVENT)));
    }

    public function testEndAnnouncesCleansAnnouncementsAndDeletesActiveEvent(): void
    {
        $activeEvent = $this->createEvent([
            'type' => EventType::WEEKLY_CURRENCY_DROPS,
            'started_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'start_date' => now()->subHours(2),
            'currently_running' => true,
        ]);

        $this->createAnnouncement([
            'event_id' => $activeEvent->id,
        ]);

        $this->service->end(new EventType(EventType::WEEKLY_CURRENCY_DROPS), $scheduledEvent->fresh(), $activeEvent->fresh());

        EventFacade::assertDispatched(GlobalMessageEvent::class);
        $this->assertEquals(0, Announcement::count());
        $this->assertEquals(0, ActiveEvent::count());
    }
}
