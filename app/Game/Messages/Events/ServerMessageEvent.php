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
    private $user;

    /**
     * @var string message
     */
    public $message;

    /**
     * @var int|null $id
     */
    public $id;

    public $isQuestItem;

    /**
     * ServerMessageEvent constructor.
     *
     * @param User $user
     * @param string $message
     * @param bool $npc
     * @param bool $isLink
     * @param string|null $link
     * @param int|null $id
     */
    public function __construct(User $user, string $message,  int $id = null, bool $isQuestItem = false) {
        $this->user        = $user;
        $this->message     = $message;
        $this->id          = $id;
        $this->isQuestItem = $isQuestItem;
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
