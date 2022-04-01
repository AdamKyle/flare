<?php

namespace App\Game\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateCharacterAttacks implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array $characterAttacks
     */
    public array $characterAttacks;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param array $characterAttacks
     */
    public function __construct(User $user, array $characterAttacks, )
    {
        $this->characterAttacks = $characterAttacks;
        $this->user           = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-character-attacks-' . $this->user->id);
    }
}
