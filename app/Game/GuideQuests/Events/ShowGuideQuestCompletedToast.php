<?php

namespace App\Game\GuideQuests\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShowGuideQuestCompletedToast implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    use SerializesModels;

    private User $user;

    public bool $showQuestCompleted;

    /**
     * Constructor
     *
     * @param User $user
     * @param bool $showToast
     */
    public function __construct(User $user, bool $showToast) {
        $this->user = $user;
        $this->showQuestCompleted = $showToast;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('guide-quest-completed-toast-' . $this->user->id);
    }
}
