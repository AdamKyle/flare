<?php

namespace App\Game\PassiveSkills\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Collection;
use App\Game\Core\Traits\KingdomCache;
Use App\Flare\Models\User;

class UpdatePassiveTree implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, KingdomCache;

    /**
     * @var User $user
     */
    private $user;

    /**
     * @var Collection $passiveSkills
     */
    public $passiveSkills;

    /**
     * @param User $user
     * @param Collection $passiveSkills
     */
    public function __construct(User $user, Collection $passiveSkills) {
        $this->user          = $user;
        $this->passiveSkills = $passiveSkills;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-passive-skills-' . $this->user->id);
    }
}
