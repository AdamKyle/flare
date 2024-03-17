<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\User;

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
     * ServerMessageEvent constructor.
     *
     * @param User $user
     * @param string $message
     * @param int|null $id
     */
    public function __construct(User $user, string $message,  int $id = null) {
        $this->user        = $user;
        $this->message     = $message;
        $this->id          = $id;
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
