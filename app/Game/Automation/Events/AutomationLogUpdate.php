<?php

namespace App\Game\Automation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class AutomationLogUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int $userId;

    public string $messageId;

    public string $message;

    public bool $makeItalic;

    public bool $isReward;

    public string $timeStamp;

    /**
     * @param int $userId
     * @param string $message
     * @param bool $makeItalic
     * @param bool $isReward
     */
    public function __construct(int $userId, string $message, bool $makeItalic = false, bool $isReward = false)
    {
        $this->userId = $userId;
        $this->messageId = Str::uuid()->toString();
        $this->message = $message;
        $this->makeItalic = $makeItalic;
        $this->isReward = $isReward;
        $this->timeStamp = now()->toJSON();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('automation-log-update-'.$this->userId);
    }
}
