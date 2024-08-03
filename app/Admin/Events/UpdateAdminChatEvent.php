<?php

namespace App\Admin\Events;

use App\Flare\Models\User;
use App\Game\Messages\Models\Message;
use Facades\App\Admin\Formatters\MessagesFormatter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateAdminChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User to be banned.
     *
     * @var User
     */
    public $user;

    /**
     * @var array
     */
    public $messages;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->messages = MessagesFormatter::format(Message::orderByDesc('id')->take(100)->get())->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('refresh-messages-'.$this->user->id);
    }
}
