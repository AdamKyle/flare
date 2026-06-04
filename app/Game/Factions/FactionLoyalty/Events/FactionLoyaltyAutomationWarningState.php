<?php

namespace App\Game\Factions\FactionLoyalty\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FactionLoyaltyAutomationWarningState implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $has_warning;

    public array $warning_notices;

    public ?array $warning_notice;

    /**
     * Create a new event instance.
     */
    public function __construct(private readonly User $user, bool $hasWarning, array $warningNotices)
    {
        $this->warning_notices = $warningNotices;
        $this->warning_notice = $warningNotices[0] ?? null;
        $this->has_warning = $hasWarning;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('faction-loyalty-automation-warning-'.$this->user->id);
    }
}
