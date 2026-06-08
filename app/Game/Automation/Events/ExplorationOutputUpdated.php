<?php

namespace App\Game\Automation\Events;

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

    public function __construct(private readonly User $user, ?string $type, ?array $output)
    {
        $this->type = $type;
        $this->output = $output;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('exploration-output-'.$this->user->id);
    }
}
