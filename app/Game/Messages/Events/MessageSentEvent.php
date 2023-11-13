<?php

namespace App\Game\Messages\Events;

use App\Flare\Values\NameTags;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Flare\Models\User;
use App\Game\Messages\Models\Message;

class MessageSentEvent implements ShouldBroadcastNow {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User $user
     */
    private User $user;

    /**
     * @var Message $message
     */
    public Message $message;

    /**
     * @var string $name
     */
    public string $name;

    /**
     * @var ?string $nameTag;
     */
    public ?string $nameTag;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param Message $message
     */
    public function __construct(User $user, Message $message) {
        if (is_null(auth()->user())) {
            return;
        }

        $nameTag = $user->name_tag;

        $this->message = $message;
        $this->name    = auth()->user()->hasRole('Admin') ? 'The Creator' : $user->character->name;
        $this->nameTag = is_null($nameTag) ? null : NameTags::$valueNames[$user->name_tag];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array {
        return new PresenceChannel('chat');
    }
}
