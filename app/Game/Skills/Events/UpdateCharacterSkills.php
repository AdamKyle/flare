<?php

namespace App\Game\Skills\Events;

use App\Flare\Models\Skill;
use App\Game\Core\Services\CharacterInventoryService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
Use App\Flare\Models\User;

class UpdateCharacterSkills implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $trainingSkills;

    public array $craftingSkills;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @param User $user
     * @param array $trainingSkills
     * @param array $craftingSkills
     */
    public function __construct(User $user, array $trainingSkills = [], array $craftingSkills = []) {
        $this->user           = $user;
        $this->trainingSkills = $trainingSkills;
        $this->craftingSkills = $craftingSkills;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('update-skill-' . $this->user->id);
    }
}
