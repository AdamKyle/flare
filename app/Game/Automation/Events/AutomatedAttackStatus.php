<?php

namespace App\Game\Automation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Flare\Models\User;

class AutomatedAttackStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var boolean $isRunning
     */
    public $isRunning;

    /**
     * @param User $user
     * @param bool $isRunning
     */
    public function __construct(User $user, bool $isRunning)
    {
        $this->user        = $user;
        $this->isRunning   = $isRunning;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('exploration-status-' . $this->user->id);
    }
}
