<?php

namespace App\Game\GuideQuests\Events;

use App\Flare\Models\User;
use App\Game\GuideQuests\Services\GuideQuestService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpenGuideQuestModal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use SerializesModels;

    private User $user;

    public bool $openButton = false;

    /**
     * Constructor
     */
    public function __construct(User $user) {

        $guideQuestService = resolve(GuideQuestService::class);

        $openModal = true;

        if (is_null($guideQuestService->fetchQuestForCharacter($user->character))) {
            $openModal = false;
        }

        $this->user = $user;
        $this->openButton = $openModal;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('force-open-guide-quest-modal-'.$this->user->id);
    }
}
