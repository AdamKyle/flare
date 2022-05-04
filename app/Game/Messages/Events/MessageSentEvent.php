<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

use App\Flare\Models\User;
use App\Game\Messages\Models\Message;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private $user;

    /**
     * @var Message $message
     */
    public $message;

    /**
     * @var string $name
     */
    public $name;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param Message $message
     * @return void
     */
    public function __construct(User $user, Message $message)
    {
        if (is_null(auth()->user())) {
            return;
        }

        $this->message = $message;
        $this->name    = auth()->user()->hasRole('Admin') ? 'Admin' : $user->character->name;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('chat');
    }
}
