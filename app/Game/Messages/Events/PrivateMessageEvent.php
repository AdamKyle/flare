<?php

namespace App\Game\Messages\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\User;
use App\Game\Messages\Models\Message;

class PrivateMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User that sent the message
     *
     * @var \App\User
     */
    public $user;

    /**
     * Message details
     *
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $from;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $from, User $user, string $message)
    {
        $this->user    = $user;
        $this->message = $message;
        $this->from    = $from->character->name;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('private-message-' . $this->user->id);
    }
}
