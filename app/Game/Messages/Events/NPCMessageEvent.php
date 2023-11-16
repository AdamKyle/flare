<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\User;

class NPCMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    private User $user;

    /**
     * @var string $message
     */
    public string $message;

    /**
     * @var string $npcName
     */
    public string $npcName;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $message
     * @param string $npcName
     */
    public function __construct(User $user, string $message, string $npcName) {
        $this->user    = $user;
        $this->message = $message;
        $this->npcName = $npcName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        // @codeCoverageIgnoreStart
        return new PrivateChannel('npc-message-' . $this->user->id);
        // @codeCoverageIgnoreEnd
    }
}
