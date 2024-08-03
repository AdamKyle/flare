<?php

namespace App\Game\Messages\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NPCMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public string $message;

    public string $npcName;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $message, string $npcName)
    {
        $this->user = $user;
        $this->message = $message;
        $this->npcName = $npcName;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        // @codeCoverageIgnoreStart
        return new PrivateChannel('npc-message-'.$this->user->id);
        // @codeCoverageIgnoreEnd
    }
}
