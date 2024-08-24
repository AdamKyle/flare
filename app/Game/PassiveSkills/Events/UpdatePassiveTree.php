<?php

namespace App\Game\PassiveSkills\Events;

use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class UpdatePassiveTree implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, KingdomCache, SerializesModels;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Collection
     */
    public $passiveSkills;

    public function __construct(User $user, Collection $passiveSkills)
    {
        $this->user = $user;
        $this->passiveSkills = $passiveSkills;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-passive-skills-'.$this->user->id);
    }
}
