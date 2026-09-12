<?php

namespace App\Game\Automation\Exploration\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExplorationDetails implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var array
     */
    public $details;

    /**
     * @param User $user The user to broadcast attack details to.
     * @param array $details The attack detail payload.
     */
    public function __construct(User $user, array $details)
    {
        $this->user = $user;
        $this->details = $details;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('automation-attack-details-'.$this->user->id);
    }
}
