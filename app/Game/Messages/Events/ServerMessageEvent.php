<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

use App\Flare\Models\User;
use App\Game\Messages\Models\Message;

class ServerMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var string message
     */
    public $message;

    /**
     * @var bool $npc
     */
    public $npc;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $message
     * @param bool $npc
     */
    public function __construct(User $user, string $message, bool $npc = false) {
        $this->user    = $user;
        $this->message = $message;
        $this->npc     = $npc;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('server-message-' . $this->user->id);
    }
}
