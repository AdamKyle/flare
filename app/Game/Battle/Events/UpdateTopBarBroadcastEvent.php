<?php

namespace App\Game\Battle\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
Use App\User;

class UpdateTopBarBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * the character sheet
     *
     * @var array
     */
    public $characterSheet;

    /**
     * The user
     *
     * @var \App\User $users
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $characterSheet, User $user)
    {
        $this->characterSheet = $characterSheet;
        $this->user           = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        new PrivateChannel('update-top-bar-' . $this->user->id);
    }
}
