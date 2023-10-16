<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class DeleteAnnouncementEvent implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * @var integer $id
     */
    public int $id;

    /**
     * Create a new event instance.
     *
     * @param int $id
     */
    public function __construct(int $id) {
        $this->id      = $id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PresenceChannel('delete-announcement-message');
    }
}
