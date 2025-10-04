<?php

namespace App\Game\Character\CharacterInventory\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharacterBoonsUpdateBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public $boons;

    /**
     * @var User
     */
    private $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $boons)
    {
        $this->user = $user;
        $this->boons = $boons;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-boons-'.$this->user->id);
    }
}
