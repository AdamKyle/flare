<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Game\Messages\Events\DeleteAnnouncementEvent;

class AnnouncementCleanupService
{
    public function deleteByEventId(int $eventId): void
    {
        $announcementId = Announcement::query()
            ->where('event_id', $eventId)
            ->value('id');

        if (is_null($announcementId)) {
            return;
        }

        event(new DeleteAnnouncementEvent($announcementId));

        Announcement::query()
            ->where('id', $announcementId)
            ->delete();
    }
}
