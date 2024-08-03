<?php

namespace App\Game\Messages\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public string $message;

    public string $from;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $from, User $user, string $message)
    {
        $this->user = $user;
        $this->message = $message;

        if ($from->hasRole('Admin')) {
            $this->from = 'The Creator';
        } else {
            $this->from = $from->character->name;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('private-message-'.$this->user->id);
    }
}
