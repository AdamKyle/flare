<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AnnouncementMessageEvent implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string $message
     */
    public string $message;

    /**
     * @var integer $id
     */
    public int $id;

    /**
     * Create a new event instance.
     *
     * - specialColor should be a css class that
     * represents the color you want applied to the message.
     *
     * @param string $message
     * @param int $id
     */
    public function __construct(string $message, int $id) {
        $this->message = $message;
        $this->id      = $id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PresenceChannel('announcement-message');
    }
}
