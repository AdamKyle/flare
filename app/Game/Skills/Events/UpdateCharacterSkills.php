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

    public $skills;

    /**
     * @var User $users
     */
    private $user;

    /**
     * @param User $user
     * @param array $skill
     */
    public function __construct(User $user, array $skills) {
        $this->user   = $user;
        $this->skills = $skills;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-skill-' . $this->user->id);
    }
}
