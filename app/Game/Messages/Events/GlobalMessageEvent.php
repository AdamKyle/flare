<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GlobalMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;

    public ?string $specialColor = null;

    /**
     * Create a new event instance.
     *
     * - specialColor should be a css class that
     * represents the color you want applied to the message.
     */
    public function __construct(string $message, string $specialColor = 'global-message')
    {
        $this->message = $message;
        $this->specialColor = $specialColor;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PresenceChannel('global-message');
    }
}
