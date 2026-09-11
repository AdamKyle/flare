<?php

namespace App\Game\Gems\Progression\Events;

use App\Game\Gems\Values\GemSourceType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GemProfileProgressionUpdateBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param GemSourceType $profileType
     * @param int $profileId
     * @param array $globalProgress
     */
    public function __construct(
        public GemSourceType $profileType,
        public int $profileId,
        public array $globalProgress,
    ) {}

    /**
     * Get the channel the event should broadcast on.
     *
     * @return Channel|PrivateChannel
     */
    public function broadcastOn(): Channel|PrivateChannel
    {
        return new PrivateChannel('gem-profile-progression-'.$this->profileType->value.'-'.$this->profileId);
    }
}
