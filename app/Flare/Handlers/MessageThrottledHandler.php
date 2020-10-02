<?php

namespace App\Flare\Handlers;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Jobs\UpdateSilencedUserJob;
use App\Flare\Models\User;

class MessageThrottledHandler {

    private $user;

    public function forUser(User $user): MessageThrottledHandler {
        $this->user = $user;

        return $this;
    }

    public function increaseThrottleCount(): MessageThrottledHandler {

        $this->user->update([
            'message_throttle_count' => $this->user->message_throttle_count + 1,
        ]);

        $this->user->refresh();

        return $this;
    }

    public function silence(): MessageThrottledHandler {

        if ($this->user->message_throttle_count >= 3) {
            $canSpeakAgainAt = now()->addMinutes(5);

            $this->user->update([
                'is_silenced' => true,
                'can_speak_again_at' =>$canSpeakAgainAt,
            ]);

            $forMessage = 'You have been silenced until: ' . $canSpeakAgainAt->format('Y-m-d H:i:s') . ' (5 minutes, server time). Making accounts to get around this is a bannable offense.';
            $user       = $this->user->refresh();
            
            event(new ServerMessageEvent($user, 'silenced', $forMessage));
            
            UpdateSilencedUserJob::dispatch($user)->delay($canSpeakAgainAt);
        }

        return $this;
    }
}