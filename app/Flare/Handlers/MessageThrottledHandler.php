<?php

namespace App\Flare\Handlers;

use App\Flare\Jobs\UpdateSilencedUserJob;
use App\Flare\Models\User;
use App\Game\Core\Events\UpdateTopBarEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class MessageThrottledHandler {

    /**
     * @var User $user
     */
    private User $user;

    /**
     * Throttle for user
     *
     * @param User $user
     * @return MessageThrottledHandler
     */
    public function forUser(User $user): MessageThrottledHandler {
        $this->user = $user;

        return $this;
    }

    /**
     * Increase the users throttle count by one.
     *
     * @return MessageThrottledHandler
     */
    public function increaseThrottleCount(): MessageThrottledHandler {

        $this->user->update([
            'message_throttle_count' => $this->user->message_throttle_count + 1,
        ]);

        $this->user->refresh();

        return $this;
    }

    /**
     * Silence the user if their throttle count is
     * above or equal to 3.
     *
     * @return MessageThrottledHandler
     */
    public function silence(): MessageThrottledHandler {

        if ($this->user->message_throttle_count >= 3) {
            $canSpeakAgainAt = now()->addMinutes(5);

            $this->user->update([
                'is_silenced' => true,
                'can_speak_again_at' =>$canSpeakAgainAt,
            ]);

            $forMessage = 'You have been silenced until: ' . $canSpeakAgainAt->format('Y-m-d H:i:s') . ' (5 minutes, server time). Making accounts to get around this is a bannable offense.';
            $user       = $this->user->refresh();

            ServerMessageHandler::handleMessage($user, 'silenced', $forMessage);

            event(new UpdateTopBarEvent($user->character));

            UpdateSilencedUserJob::dispatch($user)->delay($canSpeakAgainAt);
        }

        return $this;
    }
}
