<?php

namespace App\Game\Skills\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterSkills implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $trainingSkills;

    public array $craftingSkills;

    private User $user;

    public function __construct(User $user, array $trainingSkills = [], array $craftingSkills = [])
    {
        $this->user = $user;
        $this->trainingSkills = $trainingSkills;
        $this->craftingSkills = $craftingSkills;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-skill-'.$this->user->id);
    }
}
