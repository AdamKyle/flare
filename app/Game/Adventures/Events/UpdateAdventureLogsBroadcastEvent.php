<?php

namespace App\Game\Adventures\Events;

use App\Flare\Models\AdventureLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Collection;
Use App\Flare\Models\User;

class UpdateAdventureLogsBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Collection $adventureLogs
     */
    public $adventureLogs;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var mixed $canAdventureAgainAt
     */
    public $canAdventureAgainAt;

    /**
     * @var Collection $adventureLogs
     */
    public $isAdventuring;

    /**
     * @var bool $cancled | false
     */
    public $canceled;

    /**
     * Create a new event instance.
     *
     * @param Collection $adventureLogs
     * @param User $user
     * @param bool $cancled
     * @return void
     */
    public function __construct(Collection $adventureLogs, User $user, bool $canceled = false)
    {
        $this->adventureLogs       = $adventureLogs;
        $this->canAdventureAgainAt = $user->character->can_adventure_again_at;
        $this->user                = $user;
        $this->isAdventuring       = !is_null($user->character->adventureLogs()->where('in_progress', true)->first());
        $this->canceled            = $canceled;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-adventure-logs-' . $this->user->id);
    }
}
