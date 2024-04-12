<?php

namespace App\Game\Character\CharacterAttack\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
     * @var User $users
     */
    private $user;

    /**
     * Create a new event instance.
     *
     * @param array $attack
     * @param User $user
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
