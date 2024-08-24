<?php

namespace Tests\Traits;

use App\Flare\Models\User;
use App\Game\Messages\Models\Message;

trait CreateMessage
{
    public function createMessage(User $user, array $options = []): Message
    {
        return Message::factory()->create(array_merge(['user_id' => $user->id], $options));
    }
}
