<?php

namespace App\Flare\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\User;

class UpdateCharacterAttackBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * the character attack
     *
     * @var array
     */
    public $attack;

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
    public function __construct(array $attack, User $user)
    {
        $this->attack = $attack;
        $this->user   = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-character-attack-' . $this->user->id);
    }
}
