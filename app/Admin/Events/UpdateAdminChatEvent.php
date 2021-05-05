<?php

namespace App\Admin\Events;

use App\Game\Messages\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Facades\App\Admin\Formatters\MessagesFormatter;
use App\Flare\Models\User;

class UpdateAdminChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User to be banned.
     *
     * @var User $user
     */
    public $user;

    /**
     * @var array $messages
     */
    public $messages;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user) {
        $this->user     = $user;
        $this->messages = MessagesFormatter::format(Message::orderByDesc('id')->take(100)->get())->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('refresh-messages-' . $this->user->id);
    }
}
