<?php

namespace App\Game\Messages\Events;

use App\Flare\Models\User;
use App\Flare\Values\NameTags;
use App\Game\Messages\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    public Message $message;

    public string $name;

    /**
     * @var ?string;
     */
    public ?string $nameTag;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Message $message)
    {
        if (is_null(auth()->user())) {
            return;
        }

        $nameTag = $user->name_tag;

        $this->message = $message;
        $this->name = auth()->user()->hasRole('Admin') ? 'The Creator' : $user->character->name;
        $this->nameTag = is_null($nameTag) ? null : NameTags::$valueNames[$user->name_tag];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|array
    {
        // @codeCoverageIgnoreStart
        return new PresenceChannel('chat');
        // @codeCoverageIgnoreEnd
    }
}
