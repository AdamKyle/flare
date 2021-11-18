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

class AutomationAttackTimeOut implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var int $forLength
     */
    public $forLength;

    /**
     * @var bool $activatebar
     */
    public $activatebar;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param int $forLength | 0
     * @return void
     */
    public function __construct(User $user, int $forLength = 0)
    {
        $this->user        = $user;
        $this->forLength   = $forLength;
        $this->activatebar = true;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('automation-attack-timeout-' . $this->user->id);
    }
}
