<?php

namespace App\Game\Core\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateBaseCharacterInformation implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array  $character
     */
    public $character;

    /**
     * @var User
     */
    private $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $characterData)
    {
        $this->character = $characterData['data'];
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-character-base-stats-'.$this->user->id);
    }
}
