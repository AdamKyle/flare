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
use Illuminate\Support\Str;

class ServerMessageEvent implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var string message
     */
    public string $message;

    /**
     * @var int|null $id
     */
    public ?int $id;

    /**
     * @var bool $isQuestItem
     */
    public bool $isQuestItem;

    /**
     * ServerMessageEvent constructor.
     *
     * @param User $user
     * @param string $message
     * @param int|null $id
     * @param bool $isQuestItem
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
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('server-message-' . $this->user->id);
    }
}
