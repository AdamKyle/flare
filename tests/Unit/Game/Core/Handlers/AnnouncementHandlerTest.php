<?php

namespace Tests\Unit\Game\Core\Handlers;

use App\Flare\Models\Announcement;
use App\Game\Events\Values\EventType;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateEvent;

class AnnouncementHandlerTest extends TestCase
{
    use CreateEvent, RefreshDatabase;

    public function test_the_old_church_canonical_key_from_get_name_for_type_creates_the_announcement(): void
    {
        $event = $this->createEvent(['type' => EventType::THE_OLD_CHURCH]);

        $name = AnnouncementHandler::getNameForType(EventType::THE_OLD_CHURCH);

        AnnouncementHandler::createAnnouncement($name, $event);

        $announcement = Announcement::where('event_id', $event->id)->first();

        $this->assertNotNull($announcement);
        $this->assertStringContainsString('The Old Church', $announcement->message);
    }

    public function test_delusional_memories_canonical_key_from_get_name_for_type_creates_the_announcement(): void
    {
        $event = $this->createEvent(['type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $name = AnnouncementHandler::getNameForType(EventType::DELUSIONAL_MEMORIES_EVENT);

        AnnouncementHandler::createAnnouncement($name, $event);

        $announcement = Announcement::where('event_id', $event->id)->first();

        $this->assertNotNull($announcement);
        $this->assertStringContainsString('Delusional Memories', $announcement->message);
    }

    public function test_gold_mines_announcement_uses_the_gold_mines_event_not_the_old_church_event(): void
    {
        $oldChurchEvent = $this->createEvent(['type' => EventType::THE_OLD_CHURCH]);
        $goldMinesEvent = $this->createEvent(['type' => EventType::GOLD_MINES]);

        AnnouncementHandler::createAnnouncement('gold_mines');

        $announcement = Announcement::where('event_id', $goldMinesEvent->id)->first();

        $this->assertNotNull($announcement);
        $this->assertStringContainsString('Gold Mines', $announcement->message);
        $this->assertNull(Announcement::where('event_id', $oldChurchEvent->id)->first());
    }
}
