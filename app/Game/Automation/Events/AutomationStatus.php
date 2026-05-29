<?php

namespace App\Game\Automation\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutomationStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public bool $isRunning;

    public function __construct(User $user, bool $isRunning)
    {
        $this->user = $user;
        $this->isRunning = $isRunning;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('automation-status-'.$this->user->id);
    }
}
