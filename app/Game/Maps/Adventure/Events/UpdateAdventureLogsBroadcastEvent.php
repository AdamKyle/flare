<?php

namespace App\Game\Maps\Adventure\Events;

use App\Flare\Models\AdventureLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Collection;
Use App\User;

class UpdateAdventureLogsBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * the character sheet
     *
     * @var array
     */
    public $adventureLogs;

    /**
     * The user
     *
     * @var \App\User $users
     */
    public $user;

    public $canAdventureAgainAt;

    public $isAdventuring;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Collection $adventureLogs, User $user)
    {
        $this->adventureLogs       = $adventureLogs;
        $this->canAdventureAgainAt = $user->character->can_adventure_again_at;
        $this->user                = $user;
        $this->isAdventuring       = !is_null($user->character->adventureLogs()->where('in_progress', true)->first());
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
