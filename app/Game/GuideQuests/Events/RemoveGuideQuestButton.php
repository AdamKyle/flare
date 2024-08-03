<?php

namespace App\Game\GuideQuests\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoveGuideQuestButton implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use SerializesModels;

    private User $user;

    public bool $disableButton = false;

    /**
     * Constructor
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->disableButton = true;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('guide-quest-button-'.$this->user->id);
    }
}
