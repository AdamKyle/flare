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

class PrivateMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var string $message
     */
    public string $message;

    /**
     * @var string $from
     */
    public string $from;

    /**
     * Create a new event instance.
     *
     * @param User $from
     * @param User $user
     * @param string $message
     * @return void
     */
    public function __construct(User $from, User $user, string $message) {
        $this->user    = $user;
        $this->message = $message;

        if ($from->hasRole('Admin')) {
            $this->from = 'The Creator';
        } else {
            $this->from = $from->character->name;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PrivateChannel('private-message-' . $this->user->id);
    }
}
