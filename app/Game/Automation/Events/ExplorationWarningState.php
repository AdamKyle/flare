<?php

namespace App\Game\Automation\Events;

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

    public function __construct(private readonly User $user, bool $hasWarning, array $warnings)
    {
        $this->has_warning = $hasWarning;
        $this->warnings = $warnings;
        $this->warning = $warnings[0] ?? null;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('exploration-warning-'.$this->user->id);
    }
}
