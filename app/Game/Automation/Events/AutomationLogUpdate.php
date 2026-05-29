<?php

namespace App\Game\Automation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutomationLogUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int $userId;

    public string $message;

    public bool $makeItalic;

    public bool $isReward;

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
        return new PrivateChannel('automation-log-update-'.$this->userId);
    }
}
