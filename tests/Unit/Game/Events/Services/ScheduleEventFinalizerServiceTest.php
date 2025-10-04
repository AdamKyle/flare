<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Game\Events\Services\ScheduleEventFinalizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;
use Tests\Traits\CreateScheduledEvent;

class ScheduleEventFinalizerServiceTest extends TestCase
{
    use CreateScheduledEvent, RefreshDatabase;

    private ?ScheduleEventFinalizerService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            UpdateScheduledEvents::class,
        ]);

        $this->service = $this->app->make(ScheduleEventFinalizerService::class);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_mark_not_running_and_broadcast_updates_flag_and_dispatches_update(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'currently_running' => true,
        ]);

        $this->assertTrue((bool) $scheduledEvent->currently_running);

        $this->service->markNotRunningAndBroadcast($scheduledEvent->fresh());

        $this->assertFalse((bool) $scheduledEvent->refresh()->currently_running);

        EventFacade::assertDispatched(UpdateScheduledEvents::class);
    }
}
