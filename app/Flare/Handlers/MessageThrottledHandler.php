<?php

namespace App\Flare\Handlers;

use App\Flare\Jobs\UpdateSilencedUserJob;
use App\Flare\Models\User;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class MessageThrottledHandler
{
    private User $user;

    /**
     * Throttle for user
     */
    public function forUser(User $user): MessageThrottledHandler
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Increase the users throttle count by one.
     */
    public function increaseThrottleCount(): MessageThrottledHandler
    {

        $this->user->update([
            'message_throttle_count' => $this->user->message_throttle_count + 1,
        ]);

        $this->user->refresh();

        return $this;
    }

    /**
     * Silence the user if their throttle count is
     * above or equal to 3.
     */
    public function silence(): MessageThrottledHandler
    {

        if ($this->user->message_throttle_count >= 3) {
            $canSpeakAgainAt = now()->addMinutes(5);

            $this->user->update([
                'is_silenced' => true,
                'can_speak_again_at' => $canSpeakAgainAt,
            ]);

            $forMessage = 'You have been silenced until: '.$canSpeakAgainAt->format('Y-m-d H:i:s').' (5 minutes, server time). Making accounts to get around this is a bannable offense.';
            $user = $this->user->refresh();

            ServerMessageHandler::handleMessage($user, 'silenced', $forMessage);

            event(new UpdateCharacterBaseDetailsEvent($user->character));

            UpdateSilencedUserJob::dispatch($user)->delay($canSpeakAgainAt);
        }

        return $this;
    }
}
