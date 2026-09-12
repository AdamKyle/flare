<?php

namespace App\Game\Automation\Exploration\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExplorationWarningState implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $has_warning;

    public array $warnings;

    public ?array $warning;

    /**
     * @param User $user The user to broadcast the Exploration warning state to.
     * @param bool $hasWarning Whether the character has an active Exploration warning.
     * @param array $warnings The character's active Exploration warnings.
     */
    public function __construct(private readonly User $user, bool $hasWarning, array $warnings)
    {
        $this->has_warning = $hasWarning;
        $this->warnings = $warnings;
        $this->warning = $warnings[0] ?? null;
    }

    /**
     * Get the channel the event should broadcast on.
     *
     * @return PrivateChannel The character's private Exploration warning channel.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('exploration-warning-'.$this->user->id);
    }
}
