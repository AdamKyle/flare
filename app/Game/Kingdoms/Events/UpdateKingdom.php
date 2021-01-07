<?php

namespace App\Game\Kingdoms\Events;

use App\Flare\Models\Kingdom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateKingdom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

 
    public $kingdom;

    /**
     * @var User $users
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param bool $isDead | false
     * @return void
     */
    public function __construct(User $user, array $kingdom)
    {
        $this->user     = $user;
        $this->kingdom  = $kingdom;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-kingdom-' . $this->user->id);
    }
}
