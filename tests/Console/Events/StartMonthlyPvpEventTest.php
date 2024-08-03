<?php

namespace Tests\Console\Events;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\MonthlyPvpAutomation;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as FacadesEvent;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class StartMonthlyPvpEventTest extends TestCase
{
    use CreateAnnouncement,
        CreateEvent,
        CreateGameMap,
        CreateItem,
        CreateLocation,
        CreateMonster,
        CreateRaid,
        CreateScheduledEvent,
        RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $announcements = Announcement::all();

        foreach ($announcements as $announcement) {
            $announcement->delete();
        }

        $events = Event::all();

        foreach ($events as $event) {
            $event->delete();
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testStartPvpEvent()
    {
        (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $event = $this->createEvent([
            'type' => EventType::MONTHLY_PVP,
            'started_at' => now(),
            'ends_at' => now()->subMinute(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        FacadesEvent::fake();
        Queue::fake();

        $this->artisan('start:pvp-monthly-event');

        FacadesEvent::assertDispatched(GlobalMessageEvent::class);
        Queue::assertPushed(MonthlyPvpAutomation::class);
        FacadesEvent::assertDispatched(UpdateCharacterStatus::class);
        FacadesEvent::assertDispatched(DeleteAnnouncementEvent::class);

        $this->assertEquals(0, Announcement::count());
        $this->assertEquals(0, Event::count());
    }
}
