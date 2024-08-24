<?php

namespace App\Game\Quests\Events;

use App\Flare\Models\Event;
use App\Game\Events\Values\EventType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateQuests implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $quests;

    public bool $isWinterEvent;

    /**
     * Constructor
     */
    public function __construct(array $quests)
    {
        $this->quests = $quests;

        $this->isWinterEvent = Event::where('type', EventType::WINTER_EVENT)->count() > 0;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('update-quests');
    }
}
