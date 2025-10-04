<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement as AnnouncementModel;
use App\Game\Events\Services\AnnouncementCleanupService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;

class AnnouncementCleanupServiceTest extends TestCase
{
    use CreateAnnouncement, CreateEvent, RefreshDatabase;

    private ?AnnouncementCleanupService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AnnouncementCleanupService::class);

        EventFacade::fake([
            DeleteAnnouncementEvent::class,
        ]);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_delete_by_event_id_removes_announcement_and_dispatches_event(): void
    {
        $gameEvent = $this->createEvent([
            'type' => EventType::RAID_EVENT,
        ]);

        $announcement = $this->createAnnouncement([
            'event_id' => $gameEvent->id,
        ]);

        $this->service->deleteByEventId($gameEvent->id);

        $this->assertFalse(
            AnnouncementModel::query()->whereKey($announcement->id)->exists()
        );

        EventFacade::assertDispatched(DeleteAnnouncementEvent::class);
    }

    public function test_delete_by_event_id_when_no_announcement_found_does_nothing(): void
    {
        $gameEvent = $this->createEvent([
            'type' => EventType::RAID_EVENT,
        ]);

        $this->service->deleteByEventId($gameEvent->id);

        $this->assertSame(0, AnnouncementModel::query()->count());

        EventFacade::assertNotDispatched(DeleteAnnouncementEvent::class);
    }
}
