<?php

namespace App\Game\Messages\Events;

use App\Flare\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    /**
     * @var string message
     */
    public string $message;

    public ?int $id;

    public ?string $source;

    public ?int $itemId;

    public ?string $linkText;

    public string $timeStamp;

    /**
     * ServerMessageEvent constructor.
     */
    public function __construct(
        User $user,
        string $message,
        ?int $id = null,
        ?string $source = null,
        ?int $itemId = null,
        ?string $linkText = null,
    )
    {
        $this->user = $user;
        $this->message = $message;
        $this->id = $id;
        $this->source = $source;
        $this->itemId = $itemId;
        $this->linkText = $linkText;
        $this->timeStamp = now()->toJSON();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('server-message-'.$this->user->id);
    }
}
