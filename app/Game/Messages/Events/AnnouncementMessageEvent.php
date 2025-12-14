<?php

namespace App\Game\Messages\Events;

use App\Flare\Models\Announcement;
use App\Game\Events\Values\EventType;
use Carbon\Carbon;
use Exception;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Announcement $announcement;

    /**
     * @throws Exception
     */
    public function __construct(Announcement $announcement)
    {
        $this->announcement = $this->appendAdditionalDetails($announcement);
    }

    /**
     * @return Channel|array|Channel[]|string[]
     */
    public function broadcastOn(): Channel|array
    {
        return new PresenceChannel('announcement-message');
    }

    /**
     * @throws Exception
     */
    protected function appendAdditionalDetails(Announcement $announcement): Announcement
    {
        $announcement->expires_at_formatted = (new Carbon($announcement->expires_at))->format('l, j \of F \a\t h:ia \G\M\TP');
        $announcement->event_name = (new EventType($announcement->event->type))->getNameForEvent();

        return $announcement;
    }
}
