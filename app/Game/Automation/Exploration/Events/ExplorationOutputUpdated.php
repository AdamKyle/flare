<?php

namespace App\Game\Automation\Exploration\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExplorationOutputUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?string $type;

    public ?array $output;

    /**
     * @param User $user The user to broadcast Exploration output to.
     * @param string|null $type The output panel type.
     * @param array|null $output The output panel payload.
     */
    public function __construct(private readonly User $user, ?string $type, ?array $output)
    {
        $this->type = $type;
        $this->output = $output;
    }

    /**
     * Get the channel the event should broadcast on.
     *
     * @return PrivateChannel The character's private Exploration output channel.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('exploration-output-'.$this->user->id);
    }
}
