<?php

namespace App\Game\Core\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharacterIsDeadBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $isDead = false;

    /**
     * @var User
     */
    private $user;

    /**
     * Create a new event instance.
     *
     * @param  bool  $isDead  | false
     * @return void
     */
    public function __construct(User $user, bool $isDead = false)
    {
        $this->user = $user;
        $this->isDead = $isDead;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('character-is-dead-'.$this->user->id);
    }
}
