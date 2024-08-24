<?php

namespace App\Game\Exploration\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExplorationLogUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int $userId;

    /**
     * @var string
     */
    public $message;

    public $makeItalic;

    public $isReward;

    /**
     * @param  User  $user
     */
    public function __construct(int $userId, string $message, bool $makeItalic = false, bool $isReward = false)
    {
        $this->userId = $userId;
        $this->message = $message;
        $this->makeItalic = $makeItalic;
        $this->isReward = $isReward;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('exploration-log-update-'.$this->userId);
    }
}
